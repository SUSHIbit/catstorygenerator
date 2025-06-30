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
    public int $timeout = 1800; // 30 minutes for large content
    public int $maxExceptions = 3;

    public function __construct(Document $document)
    {
        $this->document = $document;
        $this->onQueue('cat-story-generation');
    }

    public function handle(OpenAIService $openAIService): void
    {
        Log::info("Starting large content cat story generation job for document ID: {$this->document->id}");

        try {
            // Set memory and time limits for large content
            ini_set('memory_limit', '1024M');
            set_time_limit(1800); // 30 minutes
            
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

            // Validate content before processing
            $validation = $openAIService->validateContent($this->document->original_content);
            if (!$validation['valid']) {
                throw new Exception("Content validation failed: " . implode(', ', $validation['issues']));
            }

            // Mark document as processing (for AI story generation)
            $this->document->update(['status' => 'processing']);

            // Generate the cat story (handles large content automatically)
            $catStory = $openAIService->generateCatStory($this->document);

            // Validate the generated story
            if (empty($catStory)) {
                throw new Exception("Generated cat story is empty");
            }

            if (strlen($catStory) < 20) {
                throw new Exception("Generated cat story is too short");
            }

            // Save the story and mark as completed
            $this->document->markAsCompleted($catStory);

            Log::info("Large content cat story generation completed successfully for document ID: {$this->document->id}");

        } catch (Exception $e) {
            Log::error("Large content cat story generation job failed for document ID: {$this->document->id}. Error: " . $e->getMessage());

            // Mark document as failed with specific error
            $this->document->markAsFailed("Cat story generation failed: " . $e->getMessage());
            
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Large content cat story generation job ultimately failed for document ID: {$this->document->id}. Error: " . $exception->getMessage());
        
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
        return [120, 300, 600]; // 2 min, 5 min, 10 min
    }

    /**
     * Determine the time at which the job should timeout
     */
    public function retryUntil(): \DateTime
    {
        return now()->addHours(1); // Give up after 1 hour total
    }
}