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
     * Parse document and extract text content - UNLIMITED VERSION
     */
    public function parseDocument(Document $document): bool
    {
        try {
            Log::info("Starting unlimited document parsing for document ID: {$document->id}");
            
            // REMOVED: File size limits - accept any size
            
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

            // Extract text based on file type with robust error handling
            $extractedText = match($document->file_type) {
                'pdf' => $this->extractFromPdfUnlimited($filePath),
                'doc', 'docx' => $this->extractFromWordUnlimited($filePath),
                'ppt', 'pptx' => $this->extractFromPowerPointUnlimited($filePath),
                default => throw new Exception("Unsupported file type: {$document->file_type}")
            };

            // Clean and validate extracted text
            $cleanedText = $this->cleanExtractedTextUnlimited($extractedText);
            
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
            
            // NO PAGE LIMIT - Process ALL pages
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
                    
                    // NO CONTENT LIMIT - Process everything
                    
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
                
                // NO SECTION LIMIT - Process ALL sections
                
                foreach ($section->getElements() as $element) {
                    $elementText = $this->extractTextFromWordElement($element);
                    $text .= $elementText . "\n";
                    
                    // Free memory
                    unset($elementText);
                    
                    // NO CONTENT LIMIT - Process everything
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
     * UNLIMITED PowerPoint extraction - ROBUST ERROR HANDLING
     */
    private function extractFromPowerPointUnlimited(string $filePath): string
    {
        try {
            Log::info("Processing unlimited PowerPoint extraction for: " . basename($filePath));
            
            // Try different PowerPoint readers with error handling
            $presentation = null;
            $text = '';
            
            try {
                // First attempt: Standard PowerPoint reader
                $presentation = PresentationIOFactory::load($filePath);
            } catch (Exception $e) {
                Log::warning("Standard PowerPoint reader failed: " . $e->getMessage());
                
                // Alternative approach: Try different reader types
                try {
                    $presentation = PresentationIOFactory::createReader('PowerPoint2007')->load($filePath);
                } catch (Exception $e2) {
                    Log::warning("PowerPoint2007 reader failed: " . $e2->getMessage());
                    
                    try {
                        $presentation = PresentationIOFactory::createReader('ODPresentation')->load($filePath);
                    } catch (Exception $e3) {
                        Log::warning("ODPresentation reader failed: " . $e3->getMessage());
                        throw new Exception("Could not read PowerPoint file with any available reader");
                    }
                }
            }
            
            if (!$presentation) {
                throw new Exception("Failed to load PowerPoint presentation");
            }
            
            $slideCount = 0;
            
            foreach ($presentation->getAllSlides() as $slideIndex => $slide) {
                $slideCount++;
                
                // NO SLIDE LIMIT - Process ALL slides
                
                $text .= "Slide " . ($slideIndex + 1) . ":\n";
                
                try {
                    foreach ($slide->getShapeCollection() as $shape) {
                        try {
                            if (method_exists($shape, 'getPlainText')) {
                                $slideText = $shape->getPlainText();
                                if (!empty($slideText)) {
                                    $text .= $slideText . "\n";
                                }
                                unset($slideText);
                            } elseif (method_exists($shape, 'getText')) {
                                $slideText = $shape->getText();
                                if (!empty($slideText)) {
                                    $text .= $slideText . "\n";
                                }
                                unset($slideText);
                            }
                        } catch (Exception $shapeError) {
                            Log::warning("Failed to extract text from shape in slide " . ($slideIndex + 1) . ": " . $shapeError->getMessage());
                            continue; // Skip problematic shapes
                        }
                    }
                } catch (Exception $slideError) {
                    Log::warning("Failed to process slide " . ($slideIndex + 1) . ": " . $slideError->getMessage());
                    $text .= "[Slide content could not be extracted]\n";
                    continue; // Skip problematic slides but continue
                }
                
                $text .= "\n";
                
                // Log progress every 100 slides
                if ($slideCount % 100 === 0) {
                    Log::info("Processed {$slideCount} slides");
                    gc_collect_cycles();
                }
                
                // NO CONTENT LIMIT - Process everything
            }
            
            if (empty($text)) {
                throw new Exception("PowerPoint presentation appears to be empty");
            }
            
            Log::info("Successfully extracted " . strlen($text) . " characters from PowerPoint presentation");
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
     * Clean and normalize extracted text - UNLIMITED VERSION
     */
    private function cleanExtractedTextUnlimited(string $text): string
    {
        // NO TRUNCATION LIMIT - Process all content
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