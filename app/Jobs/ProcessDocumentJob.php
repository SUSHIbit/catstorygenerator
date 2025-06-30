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
    public int $timeout = 300; // 5 minutes

    public function __construct(Document $document)
    {
        $this->document = $document;
        $this->onQueue('document-processing');
    }

    public function handle(DocumentParserService $parserService): void
    {
        Log::info("Processing document job started for document ID: {$this->document->id}");
        
        try {
            // Parse the document
            $success = $parserService->parseDocument($this->document);
            
            if ($success) {
                Log::info("Document processing completed successfully for document ID: {$this->document->id}");
                
                // Dispatch AI processing job (will be implemented in Phase 5)
                // GenerateCatStoryJob::dispatch($this->document);
            } else {
                Log::error("Document processing failed for document ID: {$this->document->id}");
            }
            
        } catch (\Exception $e) {
            Log::error("Document processing job failed for document ID: {$this->document->id}. Error: " . $e->getMessage());
            
            $this->document->markAsFailed("Processing failed: " . $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Document processing job ultimately failed for document ID: {$this->document->id}. Error: " . $exception->getMessage());
        
        $this->document->markAsFailed("Processing failed after multiple attempts: " . $exception->getMessage());
    }
}