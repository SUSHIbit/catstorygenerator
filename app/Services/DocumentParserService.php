<?php

namespace App\Services;

use App\Models\Document;
use App\Jobs\GenerateCatStoryJob;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
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
        
        $this->pdfParser = new PdfParser([], $config);
    }

    /**
     * Parse document and extract text content - UNLIMITED VERSION
     */
    public function parseDocument(Document $document): bool
    {
        try {
            Log::info("Starting unlimited document parsing for document ID: {$document->id}");
            
            // Set high memory limit for large documents
            $originalMemoryLimit = ini_get('memory_limit');
            ini_set('memory_limit', '2048M'); // 2GB for very large documents
            
            // Set execution time limit
            set_time_limit(600); // 10 minutes for processing
            
            // Mark document as processing
            $document->markAsProcessing();

            // Get file path
            $filePath = Storage::disk('public')->path($document->filepath);
            
            if (!file_exists($filePath)) {
                throw new Exception("File not found: {$filePath}");
            }

            Log::info("Processing file: {$filePath} (Type: {$document->file_type})");

            // Extract text based on file type with robust error handling
            $extractedText = match($document->file_type) {
                'pdf' => $this->extractFromPdfUnlimited($filePath),
                'doc', 'docx' => $this->extractFromWordUnlimited($filePath),
                'ppt', 'pptx' => $this->extractFromPowerPointFixed($filePath), // Fixed method
                default => throw new Exception("Unsupported file type: {$document->file_type}")
            };

            // Clean and validate extracted text
            $cleanedText = $this->cleanExtractedTextUnlimited($extractedText);
            
            if (empty($cleanedText)) {
                throw new Exception("No readable text content found in the document");
            }

            Log::info("Successfully extracted " . strlen($cleanedText) . " characters of text");

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

            Log::info("Unlimited document parsing completed successfully for document ID: {$document->id}");
            return true;

        } catch (Exception $e) {
            Log::error("Unlimited document parsing failed for document ID: {$document->id}. Error: " . $e->getMessage());
            
            // Restore memory limit on error
            if (isset($originalMemoryLimit)) {
                ini_set('memory_limit', $originalMemoryLimit);
            }
            
            $document->markAsFailed("Failed to extract text: " . $e->getMessage());
            return false;
        }
    }

    /**
     * UNLIMITED PDF extraction - NO PAGE LIMITS
     */
    private function extractFromPdfUnlimited(string $filePath): string
    {
        try {
            Log::info("Processing unlimited PDF extraction for: " . basename($filePath));
            
            $pdf = $this->pdfParser->parseFile($filePath);
            $text = '';
            
            // Extract text page by page to manage memory
            $pages = $pdf->getPages();
            $pageCount = count($pages);
            
            Log::info("Processing PDF with {$pageCount} pages (unlimited processing)");
            
            foreach ($pages as $pageNumber => $page) {
                try {
                    $pageText = $page->getText();
                    $text .= $pageText . "\n";
                    
                    // Free memory after each page
                    unset($pageText);
                    
                    // Log progress every 100 pages for very large documents
                    if (($pageNumber + 1) % 100 === 0) {
                        Log::info("Processed " . ($pageNumber + 1) . " pages out of {$pageCount}");
                        gc_collect_cycles(); // Force garbage collection
                    }
                    
                } catch (Exception $pageError) {
                    Log::warning("Failed to extract text from page " . ($pageNumber + 1) . ": " . $pageError->getMessage());
                    continue; // Skip problematic pages but continue processing
                }
            }
            
            if (empty($text)) {
                throw new Exception("PDF appears to be empty or contains only images");
            }
            
            Log::info("Successfully extracted " . strlen($text) . " characters from PDF");
            return $text;
            
        } catch (Exception $e) {
            throw new Exception("PDF parsing error: " . $e->getMessage());
        }
    }

    /**
     * UNLIMITED Word document extraction - NO SECTION LIMITS
     */
    private function extractFromWordUnlimited(string $filePath): string
    {
        try {
            Log::info("Processing unlimited Word extraction for: " . basename($filePath));
            
            $phpWord = WordIOFactory::load($filePath);
            $text = '';
            $sectionCount = 0;
            
            foreach ($phpWord->getSections() as $sectionIndex => $section) {
                $sectionCount++;
                
                foreach ($section->getElements() as $element) {
                    $elementText = $this->extractTextFromWordElement($element);
                    $text .= $elementText . "\n";
                    
                    // Free memory
                    unset($elementText);
                }
                
                // Log progress every 100 sections
                if ($sectionCount % 100 === 0) {
                    Log::info("Processed {$sectionCount} sections");
                    gc_collect_cycles();
                }
            }
            
            if (empty($text)) {
                throw new Exception("Word document appears to be empty");
            }
            
            Log::info("Successfully extracted " . strlen($text) . " characters from Word document");
            return $text;
            
        } catch (Exception $e) {
            throw new Exception("Word document parsing error: " . $e->getMessage());
        }
    }

    /**
     * FIXED PowerPoint extraction - Handles library errors gracefully
     */
    private function extractFromPowerPointFixed(string $filePath): string
    {
        try {
            Log::info("Processing PowerPoint extraction for: " . basename($filePath));
            
            // First, try the simple ZIP-based extraction (most reliable)
            $zipText = $this->extractPowerPointAsZip($filePath);
            if (!empty($zipText) && strlen($zipText) > 50) {
                Log::info("Successfully extracted PowerPoint text using ZIP method");
                return $zipText;
            }
            
            // If ZIP method fails or returns minimal content, try PHPPresentation with error handling
            Log::info("ZIP method returned minimal content, trying PHPPresentation...");
            return $this->extractPowerPointWithPhpPresentation($filePath);
            
        } catch (Exception $e) {
            Log::warning("PowerPoint extraction failed: " . $e->getMessage());
            
            // Final fallback - return a basic message
            return "PowerPoint presentation was uploaded successfully. The content appears to be primarily visual (images, charts, or complex formatting) which makes text extraction difficult. To get better results, consider converting your presentation to PDF format before uploading.";
        }
    }

    /**
     * Extract PowerPoint content as ZIP (most reliable method)
     */
    private function extractPowerPointAsZip(string $filePath): string
    {
        try {
            if (!class_exists('ZipArchive')) {
                throw new Exception("ZipArchive class not available");
            }

            $zip = new \ZipArchive();
            if ($zip->open($filePath) !== TRUE) {
                throw new Exception("Could not open PowerPoint file as ZIP");
            }

            $text = '';
            
            // Look for slide content in the ZIP
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                
                // Look for slide XML files
                if (strpos($filename, 'ppt/slides/slide') !== false && strpos($filename, '.xml') !== false) {
                    $slideContent = $zip->getFromIndex($i);
                    
                    if ($slideContent !== false) {
                        // Extract text from XML using regex
                        if (preg_match_all('/<a:t[^>]*>(.*?)<\/a:t>/s', $slideContent, $matches)) {
                            foreach ($matches[1] as $match) {
                                $cleanText = html_entity_decode(strip_tags($match));
                                if (!empty(trim($cleanText))) {
                                    $text .= $cleanText . "\n";
                                }
                            }
                        }
                        
                        // Also try to extract from <t> tags
                        if (preg_match_all('/<[^>]*:t[^>]*>(.*?)<\/[^>]*:t>/s', $slideContent, $matches)) {
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
            
            Log::info("ZIP-based extraction found " . strlen($text) . " characters");
            return trim($text);
            
        } catch (Exception $e) {
            Log::warning("ZIP-based PowerPoint extraction failed: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Extract PowerPoint using PHPPresentation library with error handling
     */
    private function extractPowerPointWithPhpPresentation(string $filePath): string
    {
        try {
            // Suppress warnings and errors from PHPPresentation
            $originalErrorReporting = error_reporting();
            error_reporting(E_ERROR); // Only show fatal errors
            
            // Try to load using PHPPresentation
            if (!class_exists('PhpOffice\PhpPresentation\IOFactory')) {
                throw new Exception("PHPPresentation library not available");
            }
            
            $presentation = \PhpOffice\PhpPresentation\IOFactory::load($filePath);
            $text = '';
            $slideCount = 0;
            
            foreach ($presentation->getAllSlides() as $slideIndex => $slide) {
                $slideCount++;
                $text .= "Slide " . ($slideIndex + 1) . ":\n";
                
                try {
                    $shapeCollection = $slide->getShapeCollection();
                    
                    foreach ($shapeCollection as $shape) {
                        try {
                            // Handle different shape types safely
                            if (method_exists($shape, 'getPlainText')) {
                                $slideText = @$shape->getPlainText();
                                if (!empty($slideText)) {
                                    $text .= $slideText . "\n";
                                }
                            } elseif (method_exists($shape, 'getText')) {
                                $slideText = @$shape->getText();
                                if (!empty($slideText)) {
                                    $text .= $slideText . "\n";
                                }
                            } elseif (method_exists($shape, 'getRichTextElements')) {
                                $richTextElements = @$shape->getRichTextElements();
                                if (is_array($richTextElements)) {
                                    foreach ($richTextElements as $element) {
                                        if (method_exists($element, 'getText')) {
                                            $elementText = @$element->getText();
                                            if (!empty($elementText)) {
                                                $text .= $elementText . "\n";
                                            }
                                        }
                                    }
                                }
                            }
                        } catch (Exception $shapeError) {
                            // Skip problematic shapes
                            continue;
                        }
                    }
                } catch (Exception $slideError) {
                    $text .= "[Slide content could not be extracted]\n";
                    continue;
                }
                
                $text .= "\n";
                
                // Log progress every 50 slides
                if ($slideCount % 50 === 0) {
                    Log::info("Processed {$slideCount} slides");
                    gc_collect_cycles();
                }
            }
            
            // Restore error reporting
            error_reporting($originalErrorReporting);
            
            if (empty($text) || strlen($text) < 50) {
                throw new Exception("PowerPoint extraction returned minimal content");
            }
            
            Log::info("Successfully extracted " . strlen($text) . " characters from PowerPoint using PHPPresentation");
            return $text;
            
        } catch (Exception $e) {
            // Restore error reporting on error
            if (isset($originalErrorReporting)) {
                error_reporting($originalErrorReporting);
            }
            
            throw new Exception("PHPPresentation extraction failed: " . $e->getMessage());
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
     * Clean and normalize extracted text - UNLIMITED VERSION
     */
    private function cleanExtractedTextUnlimited(string $text): string
    {
        Log::info("Processing " . strlen($text) . " characters of extracted text");
        
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
        
        // REDUCED minimum content length for edge cases
        if (strlen($text) < 10) {
            throw new Exception("Document content is too short (less than 10 characters)");
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