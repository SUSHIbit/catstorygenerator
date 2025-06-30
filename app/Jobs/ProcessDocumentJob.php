<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\DocumentParserService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Document $document;
    public int $tries = 3;
    public int $timeout = 1800; // 30 minutes
    public int $maxExceptions = 3;

    public function __construct(Document $document)
    {
        $this->document = $document;
        // Remove queue specification to use default
    }

    public function handle(DocumentParserService $parserService): void
    {
        Log::info("Starting document processing for document ID: {$this->document->id}");
        
        try {
            // Set high limits for large files
            ini_set('memory_limit', '1024M');
            set_time_limit(1800); // 30 minutes
            
            // Refresh document to get latest data
            $this->document->refresh();
            
            // Check if document file exists
            if (!file_exists(storage_path('app/public/' . $this->document->filepath))) {
                throw new \Exception("Document file not found: {$this->document->filepath}");
            }
            
            Log::info("Processing document: {$this->document->title} (Type: {$this->document->file_type}, Size: {$this->document->file_size_formatted})");
            
            // Parse the document
            $success = $parserService->parseDocument($this->document);
            
            if ($success) {
                Log::info("Document processing completed successfully for document ID: {$this->document->id}");
            } else {
                Log::error("Document processing failed for document ID: {$this->document->id}");
                throw new \Exception("Document parsing failed");
            }
            
        } catch (\Exception $e) {
            Log::error("Document processing job failed for document ID: {$this->document->id}. Error: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            
            $this->document->markAsFailed("Processing failed: " . $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Document processing job ultimately failed for document ID: {$this->document->id}. Error: " . $exception->getMessage());
        
        // Ensure document is marked as failed if not already
        if (!$this->document->isFailed()) {
            $this->document->markAsFailed("Processing failed after multiple attempts: " . $exception->getMessage());
        }
    }

    /**
     * Calculate backoff delays for retries
     */
    public function backoff(): array
    {
        return [60, 180, 300]; // 1 min, 3 min, 5 min
    }

    /**
     * Determine the time at which the job should timeout
     */
    public function retryUntil(): \DateTime
    {
        return now()->addHours(1); // Give up after 1 hour total
    }
}