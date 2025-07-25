<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\OpenAIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class GenerateCatStoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Document $document;
    public int $tries = 3;
    public int $timeout = 900; // 15 minutes
    public int $maxExceptions = 3;

    public function __construct(Document $document)
    {
        $this->document = $document;
        // Remove queue specification to use default
    }

    public function handle(OpenAIService $openAIService): void
    {
        Log::info("Starting cat story generation for document ID: {$this->document->id}");

        try {
            // Set memory and time limits
            ini_set('memory_limit', '512M');
            set_time_limit(900); // 15 minutes
            
            // Refresh document to get latest data
            $this->document->refresh();

            // Check if document has content to work with
            if (empty($this->document->original_content)) {
                throw new Exception("Document has no extracted content to transform");
            }

            // Check if story already exists (avoid regenerating)
            if ($this->document->hasStory()) {
                Log::info("Document ID: {$this->document->id} already has a cat story, skipping generation");
                return;
            }

            Log::info("Generating cat story for document: {$this->document->title} (Content length: " . strlen($this->document->original_content) . " characters)");

            // Mark document as processing (for AI story generation)
            $this->document->update(['status' => 'processing']);

            // Validate content before processing
            $validation = $openAIService->validateContent($this->document->original_content);
            if (!$validation['valid']) {
                throw new Exception("Content validation failed: " . implode(', ', $validation['issues']));
            }

            // Generate the cat story
            $catStory = $openAIService->generateCatStory($this->document);

            // Validate the generated story
            if (empty($catStory)) {
                throw new Exception("Generated cat story is empty");
            }

            if (strlen($catStory) < 20) {
                throw new Exception("Generated cat story is too short (less than 20 characters)");
            }

            // Save the story and mark as completed
            $this->document->markAsCompleted($catStory);

            Log::info("Cat story generation completed successfully for document ID: {$this->document->id}");

        } catch (Exception $e) {
            Log::error("Cat story generation job failed for document ID: {$this->document->id}. Error: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());

            // Mark document as failed with specific error
            $this->document->markAsFailed("Cat story generation failed: " . $e->getMessage());
            
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Cat story generation job ultimately failed for document ID: {$this->document->id}. Error: " . $exception->getMessage());
        
        // Ensure document is marked as failed if not already
        if (!$this->document->isFailed()) {
            $this->document->markAsFailed("Cat story generation failed after multiple attempts: " . $exception->getMessage());
        }
    }

    /**
     * Calculate backoff delays for retries
     */
    public function backoff(): array
    {
        return [60, 120, 180]; // 1 min, 2 min, 3 min
    }

    /**
     * Determine the time at which the job should timeout
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(30); // Give up after 30 minutes total
    }
}