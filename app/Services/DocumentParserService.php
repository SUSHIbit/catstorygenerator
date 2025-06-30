<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpPresentation\IOFactory as PresentationIOFactory;
use Smalot\PdfParser\Parser as PdfParser;
use Exception;

class DocumentParserService
{
    private PdfParser $pdfParser;

    public function __construct()
    {
        $this->pdfParser = new PdfParser();
    }

    /**
     * Parse document and extract text content
     */
    public function parseDocument(Document $document): bool
    {
        try {
            Log::info("Starting document parsing for document ID: {$document->id}");
            
            // Mark document as processing
            $document->markAsProcessing();

            // Get file path
            $filePath = Storage::disk('public')->path($document->filepath);
            
            if (!file_exists($filePath)) {
                throw new Exception("File not found: {$filePath}");
            }

            // Extract text based on file type
            $extractedText = match($document->file_type) {
                'pdf' => $this->extractFromPdf($filePath),
                'doc', 'docx' => $this->extractFromWord($filePath),
                'ppt', 'pptx' => $this->extractFromPowerPoint($filePath),
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

            Log::info("Document parsing completed successfully for document ID: {$document->id}");
            return true;

        } catch (Exception $e) {
            Log::error("Document parsing failed for document ID: {$document->id}. Error: " . $e->getMessage());
            
            $document->markAsFailed("Failed to extract text: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Extract text from PDF files
     */
    private function extractFromPdf(string $filePath): string
    {
        try {
            $pdf = $this->pdfParser->parseFile($filePath);
            $text = $pdf->getText();
            
            if (empty($text)) {
                throw new Exception("PDF appears to be empty or contains only images");
            }
            
            return $text;
            
        } catch (Exception $e) {
            throw new Exception("PDF parsing error: " . $e->getMessage());
        }
    }

    /**
     * Extract text from Word documents (.doc, .docx)
     */
    private function extractFromWord(string $filePath): string
    {
        try {
            $phpWord = WordIOFactory::load($filePath);
            $text = '';
            
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    $text .= $this->extractTextFromWordElement($element) . "\n";
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
     * Extract text from PowerPoint presentations (.ppt, .pptx)
     */
    private function extractFromPowerPoint(string $filePath): string
    {
        try {
            $presentation = PresentationIOFactory::load($filePath);
            $text = '';
            
            foreach ($presentation->getAllSlides() as $slideIndex => $slide) {
                $text .= "Slide " . ($slideIndex + 1) . ":\n";
                
                foreach ($slide->getShapeCollection() as $shape) {
                    if (method_exists($shape, 'getPlainText')) {
                        $slideText = $shape->getPlainText();
                        if (!empty($slideText)) {
                            $text .= $slideText . "\n";
                        }
                    }
                }
                $text .= "\n";
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
        
        if (method_exists($element, 'getText')) {
            $text .= $element->getText();
        } elseif (method_exists($element, 'getElements')) {
            foreach ($element->getElements() as $childElement) {
                $text .= $this->extractTextFromWordElement($childElement);
            }
        }
        
        return $text;
    }

    /**
     * Clean and normalize extracted text
     */
    private function cleanExtractedText(string $text): string
    {
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
        
        // Limit maximum content length to prevent memory issues
        if (strlen($text) > 100000) {
            $text = substr($text, 0, 100000) . "\n\n[Content truncated due to length...]";
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
}