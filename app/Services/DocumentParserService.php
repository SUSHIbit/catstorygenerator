<?php

namespace App\Services;

use App\Models\Document;
use App\Jobs\GenerateCatStoryJob;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpPresentation\IOFactory as PresentationIOFactory;
use Smalot\PdfParser\Parser as PdfParser;
use Smalot\PdfParser\Config;
use Exception;

class DocumentParserService
{
    private PdfParser $pdfParser;

    public function __construct()
    {
        // Configure PDF parser with memory-safe settings
        $config = new Config();
        $config->setRetainImageContent(false); // Don't load images
        $config->setIgnoreEncryption(true);
        $config->setHorizontalOffset('');
        
        $this->pdfParser = new PdfParser([], $config);
    }

    /**
     * Parse document and extract text content with memory management
     */
    public function parseDocument(Document $document): bool
    {
        try {
            Log::info("Starting document parsing for document ID: {$document->id}");
            
            // Check file size first
            if ($document->file_size > 10 * 1024 * 1024) { // 10MB
                throw new Exception("File too large for processing (max 10MB)");
            }
            
            // Increase memory limit for this operation
            $originalMemoryLimit = ini_get('memory_limit');
            ini_set('memory_limit', '512M');
            
            // Mark document as processing
            $document->markAsProcessing();

            // Get file path
            $filePath = Storage::disk('public')->path($document->filepath);
            
            if (!file_exists($filePath)) {
                throw new Exception("File not found: {$filePath}");
            }

            // Extract text based on file type with memory management
            $extractedText = match($document->file_type) {
                'pdf' => $this->extractFromPdfSafe($filePath),
                'doc', 'docx' => $this->extractFromWordSafe($filePath),
                'ppt', 'pptx' => $this->extractFromPowerPointSafe($filePath),
                default => throw new Exception("Unsupported file type: {$document->file_type}")
            };

            // Clean and validate extracted text
            $cleanedText = $this->cleanExtractedText($extractedText);
            
            if (empty($cleanedText)) {
                throw new Exception("No readable text content found in the document");
            }

            // Update document with extracted content
            $document->update([
                'original_content' => $cleanedText,
                'status' => 'uploaded' // Ready for AI processing
            ]);

            // Free memory
            unset($extractedText, $cleanedText);
            gc_collect_cycles();
            
            // Restore original memory limit
            ini_set('memory_limit', $originalMemoryLimit);

            // Dispatch AI processing job to generate cat story
            GenerateCatStoryJob::dispatch($document);

            Log::info("Document parsing completed successfully for document ID: {$document->id}");
            return true;

        } catch (Exception $e) {
            Log::error("Document parsing failed for document ID: {$document->id}. Error: " . $e->getMessage());
            
            // Restore memory limit on error
            if (isset($originalMemoryLimit)) {
                ini_set('memory_limit', $originalMemoryLimit);
            }
            
            $document->markAsFailed("Failed to extract text: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Memory-safe PDF extraction
     */
    private function extractFromPdfSafe(string $filePath): string
    {
        try {
            // Check file size before processing
            $fileSize = filesize($filePath);
            if ($fileSize > 5 * 1024 * 1024) { // 5MB limit for PDFs
                throw new Exception("PDF file too large for text extraction");
            }
            
            $pdf = $this->pdfParser->parseFile($filePath);
            $text = '';
            
            // Extract text page by page to manage memory
            $pages = $pdf->getPages();
            $pageCount = count($pages);
            
            if ($pageCount > 50) {
                throw new Exception("PDF has too many pages (max 50 pages)");
            }
            
            foreach ($pages as $pageNumber => $page) {
                $pageText = $page->getText();
                $text .= $pageText . "\n";
                
                // Free memory after each page
                unset($pageText);
                
                // Stop if we have enough content
                if (strlen($text) > 50000) {
                    $text .= "\n[Content truncated - document too long]";
                    break;
                }
            }
            
            if (empty($text)) {
                throw new Exception("PDF appears to be empty or contains only images");
            }
            
            return $text;
            
        } catch (Exception $e) {
            throw new Exception("PDF parsing error: " . $e->getMessage());
        }
    }

    /**
     * Memory-safe Word document extraction
     */
    private function extractFromWordSafe(string $filePath): string
    {
        try {
            $fileSize = filesize($filePath);
            if ($fileSize > 5 * 1024 * 1024) { // 5MB limit
                throw new Exception("Word document too large for text extraction");
            }
            
            $phpWord = WordIOFactory::load($filePath);
            $text = '';
            
            foreach ($phpWord->getSections() as $sectionIndex => $section) {
                foreach ($section->getElements() as $element) {
                    $elementText = $this->extractTextFromWordElement($element);
                    $text .= $elementText . "\n";
                    
                    // Free memory
                    unset($elementText);
                    
                    // Stop if we have enough content
                    if (strlen($text) > 50000) {
                        $text .= "\n[Content truncated - document too long]";
                        break 2;
                    }
                }
            }
            
            if (empty($text)) {
                throw new Exception("Word document appears to be empty");
            }
            
            return $text;
            
        } catch (Exception $e) {
            throw new Exception("Word document parsing error: " . $e->getMessage());
        }
    }

    /**
     * Memory-safe PowerPoint extraction
     */
    private function extractFromPowerPointSafe(string $filePath): string
    {
        try {
            $fileSize = filesize($filePath);
            if ($fileSize > 5 * 1024 * 1024) { // 5MB limit
                throw new Exception("PowerPoint file too large for text extraction");
            }
            
            $presentation = PresentationIOFactory::load($filePath);
            $text = '';
            $slideCount = 0;
            
            foreach ($presentation->getAllSlides() as $slideIndex => $slide) {
                $slideCount++;
                
                if ($slideCount > 30) { // Limit slides
                    $text .= "\n[Remaining slides truncated - too many slides]";
                    break;
                }
                
                $text .= "Slide " . ($slideIndex + 1) . ":\n";
                
                foreach ($slide->getShapeCollection() as $shape) {
                    if (method_exists($shape, 'getPlainText')) {
                        $slideText = $shape->getPlainText();
                        if (!empty($slideText)) {
                            $text .= $slideText . "\n";
                        }
                        unset($slideText);
                    }
                }
                $text .= "\n";
                
                // Stop if we have enough content
                if (strlen($text) > 50000) {
                    $text .= "\n[Content truncated - document too long]";
                    break;
                }
            }
            
            if (empty($text)) {
                throw new Exception("PowerPoint presentation appears to be empty");
            }
            
            return $text;
            
        } catch (Exception $e) {
            throw new Exception("PowerPoint parsing error: " . $e->getMessage());
        }
    }

    /**
     * Extract text from Word document elements recursively
     */
    private function extractTextFromWordElement($element): string
    {
        $text = '';
        
        try {
            if (method_exists($element, 'getText')) {
                $text .= $element->getText();
            } elseif (method_exists($element, 'getElements')) {
                foreach ($element->getElements() as $childElement) {
                    $text .= $this->extractTextFromWordElement($childElement);
                }
            }
        } catch (Exception $e) {
            // Skip problematic elements
            Log::warning("Skipped problematic Word element: " . $e->getMessage());
        }
        
        return $text;
    }

    /**
     * Clean and normalize extracted text with memory management
     */
    private function cleanExtractedText(string $text): string
    {
        // Truncate if too long before processing
        if (strlen($text) > 100000) {
            $text = substr($text, 0, 100000) . "\n\n[Content truncated due to length...]";
        }
        
        // Remove excessive whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Remove control characters except newlines and tabs
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        
        // Normalize line breaks
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        
        // Remove excessive line breaks
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        
        // Trim whitespace
        $text = trim($text);
        
        // Ensure minimum content length
        if (strlen($text) < 50) {
            throw new Exception("Document content is too short (less than 50 characters)");
        }
        
        return $text;
    }

    /**
     * Get document processing statistics
     */
    public function getProcessingStats(Document $document): array
    {
        $stats = [
            'file_size' => $document->file_size,
            'file_type' => $document->file_type,
            'has_content' => !empty($document->original_content),
            'content_length' => $document->original_content ? strlen($document->original_content) : 0,
            'word_count' => $document->original_content ? str_word_count($document->original_content) : 0,
            'estimated_reading_time' => 0
        ];
        
        // Calculate estimated reading time (average 200 words per minute)
        if ($stats['word_count'] > 0) {
            $stats['estimated_reading_time'] = ceil($stats['word_count'] / 200);
        }
        
        return $stats;
    }

    /**
     * Manually trigger cat story generation for a document
     */
    public function generateCatStoryNow(Document $document): bool
    {
        try {
            if (empty($document->original_content)) {
                throw new Exception("Document has no content to transform into cat story");
            }

            if ($document->hasStory()) {
                Log::info("Document ID: {$document->id} already has a cat story");
                return true;
            }

            // Dispatch the cat story generation job
            GenerateCatStoryJob::dispatch($document);
            
            Log::info("Cat story generation manually triggered for document ID: {$document->id}");
            return true;

        } catch (Exception $e) {
            Log::error("Failed to trigger cat story generation for document ID: {$document->id}. Error: " . $e->getMessage());
            return false;
        }
    }
}