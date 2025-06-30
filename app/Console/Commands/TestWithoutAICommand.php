<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Services\DocumentParserService;
use Illuminate\Console\Command;

class TestWithoutAICommand extends Command
{
    protected $signature = 'test:no-ai {document_id?}';
    protected $description = 'Test document processing without AI (text extraction only)';

    public function handle(): int
    {
        $documentId = $this->argument('document_id');
        
        if ($documentId) {
            $document = Document::find($documentId);
            if (!$document) {
                $this->error("Document with ID {$documentId} not found.");
                return 1;
            }
        } else {
            // Find a non-PowerPoint document first
            $document = Document::whereNotIn('file_type', ['ppt', 'pptx'])->latest()->first();
            
            if (!$document) {
                $document = Document::latest()->first();
                if (!$document) {
                    $this->error("No documents found in database.");
                    $this->info("Please upload a document through the web interface first.");
                    return 1;
                }
            }
        }

        $this->info("Testing text extraction for document: {$document->title} (ID: {$document->id})");
        $this->info("File type: {$document->file_type}");
        $this->info("File size: {$document->file_size_formatted}");

        // Test 1: Check file exists
        $filePath = storage_path('app/public/' . $document->filepath);
        if (!file_exists($filePath)) {
            $this->error("❌ File not found: {$filePath}");
            return 1;
        }
        $this->info("✅ File exists: {$filePath}");

        // Test 2: Test document parsing (text extraction only)
        $parserService = app(DocumentParserService::class);
        $this->info("🔄 Testing text extraction from {$document->file_type} file...");
        
        try {
            // Manually extract text without triggering AI job
            $originalMemoryLimit = ini_get('memory_limit');
            ini_set('memory_limit', '1024M');
            set_time_limit(300); // 5 minutes
            
            $document->markAsProcessing();
            
            $extractedText = '';
            
            switch($document->file_type) {
                case 'pdf':
                    $extractedText = $this->extractPdfText($filePath);
                    break;
                case 'doc':
                case 'docx':
                    $extractedText = $this->extractWordText($filePath);
                    break;
                case 'ppt':
                case 'pptx':
                    $this->warn("⚠️  PowerPoint files may have extraction issues");
                    $extractedText = $this->extractPowerPointText($filePath);
                    break;
                default:
                    throw new \Exception("Unsupported file type: {$document->file_type}");
            }
            
            $cleanedText = $this->cleanText($extractedText);
            
            if (empty($cleanedText)) {
                throw new \Exception("No readable text content found in the document");
            }
            
            // Save extracted content without triggering AI job
            $document->update([
                'original_content' => $cleanedText,
                'status' => 'uploaded' // Don't mark as completed to avoid AI job
            ]);
            
            ini_set('memory_limit', $originalMemoryLimit);
            
            $this->info("✅ Text extraction successful!");
            $this->info("   Extracted content length: " . strlen($cleanedText) . " characters");
            $this->info("   Word count: " . str_word_count($cleanedText));
            
            // Show preview
            $preview = substr($cleanedText, 0, 200);
            $this->line("📝 Content preview:");
            $this->line("   " . $preview . "...");
            
            $this->info("💡 To test AI story generation separately, run:");
            $this->info("   php artisan diagnose:openai");
            
        } catch (\Exception $e) {
            $this->error("❌ Text extraction failed: " . $e->getMessage());
            
            if (isset($originalMemoryLimit)) {
                ini_set('memory_limit', $originalMemoryLimit);
            }
            
            $document->markAsFailed("Text extraction failed: " . $e->getMessage());
            return 1;
        }
        
        $this->info("🎉 Text extraction test completed successfully!");
        return 0;
    }
    
    private function extractPdfText(string $filePath): string
    {
        $config = new \Smalot\PdfParser\Config();
        $config->setRetainImageContent(false);
        $config->setIgnoreEncryption(true);
        
        $parser = new \Smalot\PdfParser\Parser([], $config);
        $pdf = $parser->parseFile($filePath);
        
        $text = '';
        foreach ($pdf->getPages() as $page) {
            $text .= $page->getText() . "\n";
        }
        
        return $text;
    }
    
    private function extractWordText(string $filePath): string
    {
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($filePath);
        $text = '';
        
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $text .= $this->extractWordElementText($element) . "\n";
            }
        }
        
        return $text;
    }
    
    private function extractWordElementText($element): string
    {
        $text = '';
        
        try {
            if (method_exists($element, 'getText')) {
                $text .= $element->getText();
            } elseif (method_exists($element, 'getElements')) {
                foreach ($element->getElements() as $childElement) {
                    $text .= $this->extractWordElementText($childElement);
                }
            }
        } catch (\Exception $e) {
            // Skip problematic elements
        }
        
        return $text;
    }
    
    private function extractPowerPointText(string $filePath): string
    {
        // Use simple ZIP-based extraction to avoid PHPPresentation issues
        try {
            if (!class_exists('ZipArchive')) {
                throw new \Exception("ZipArchive not available");
            }

            $zip = new \ZipArchive();
            if ($zip->open($filePath) !== TRUE) {
                throw new \Exception("Could not open PowerPoint file");
            }

            $text = '';
            
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                
                if (strpos($filename, 'ppt/slides/slide') !== false && strpos($filename, '.xml') !== false) {
                    $slideContent = $zip->getFromIndex($i);
                    
                    if ($slideContent !== false) {
                        if (preg_match_all('/<a:t[^>]*>(.*?)<\/a:t>/s', $slideContent, $matches)) {
                            foreach ($matches[1] as $match) {
                                $cleanText = html_entity_decode(strip_tags($match));
                                if (!empty(trim($cleanText))) {
                                    $text .= $cleanText . "\n";
                                }
                            }
                        }
                    }
                }
            }
            
            $zip->close();
            return $text;
            
        } catch (\Exception $e) {
            return "PowerPoint text extraction failed: " . $e->getMessage() . ". This file may contain primarily visual content.";
        }
    }
    
    private function cleanText(string $text): string
    {
        // Remove excessive whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Remove control characters
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        
        // Normalize line breaks
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        
        // Remove excessive line breaks
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        
        return trim($text);
    }
}