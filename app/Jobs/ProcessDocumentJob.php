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
    public int $timeout = 3600; // 1 hour for very large files
    public int $maxExceptions = 3;

    public function __construct(Document $document)
    {
        $this->document = $document;
        $this->onQueue('document-processing');
    }

    public function handle(DocumentParserService $parserService): void
    {
        Log::info("Processing large document job started for document ID: {$this->document->id}");
        
        try {
            // Set memory and time limits for large files
            ini_set('memory_limit', '2048M');
            set_time_limit(3600); // 1 hour
            
            // Parse the document
            $success = $parserService->parseDocument($this->document);
            
            if ($success) {
                Log::info("Large document processing completed successfully for document ID: {$this->document->id}");
            } else {
                Log::error("Large document processing failed for document ID: {$this->document->id}");
            }
            
        } catch (\Exception $e) {
            Log::error("Large document processing job failed for document ID: {$this->document->id}. Error: " . $e->getMessage());
            
            $this->document->markAsFailed("Processing failed: " . $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Large document processing job ultimately failed for document ID: {$this->document->id}. Error: " . $exception->getMessage());
        
        $this->document->markAsFailed("Processing failed after multiple attempts: " . $exception->getMessage());
    }

    /**
     * Calculate backoff delays for retries
     */
    public function backoff(): array
    {
        return [60, 300, 900]; // 1 min, 5 min, 15 min
    }

    /**
     * Determine the time at which the job should timeout
     */
    public function retryUntil(): \DateTime
    {
        return now()->addHours(2); // Give up after 2 hours total
    }
}