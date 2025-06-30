<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;

class OpenAIService
{
    private string $apiKey;
    private int $maxTokens;
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key') ?? env('OPENAI_API_KEY');
        $this->maxTokens = 2000;
        $this->model = 'gpt-3.5-turbo';
        
        if (empty($this->apiKey)) {
            throw new Exception('OpenAI API key not configured');
        }
    }

    /**
     * Generate a cat story from document content - HANDLES LARGE CONTENT
     */
    public function generateCatStory(Document $document): string
    {
        try {
            Log::info("Starting cat story generation for document ID: {$document->id}");

            if (empty($document->original_content)) {
                throw new Exception("No content available to transform into cat story");
            }

            // Handle very large content by chunking
            $content = $document->original_content;
            $contentLength = strlen($content);
            
            Log::info("Processing {$contentLength} characters for cat story generation");
            
            // If content is very large, use summary approach
            if ($contentLength > 100000) { // 100KB
                Log::info("Large content detected, using chunked processing");
                return $this->generateCatStoryForLargeContent($content);
            } else {
                return $this->generateCatStoryForNormalContent($content);
            }

        } catch (Exception $e) {
            Log::error("OpenAI cat story generation failed for document ID: {$document->id}. Error: " . $e->getMessage());
            throw new Exception("Failed to generate cat story: " . $e->getMessage());
        }
    }

    /**
     * Generate cat story for normal-sized content
     */
    private function generateCatStoryForNormalContent(string $content): string
    {
        $prompt = $this->buildCatStoryPrompt($content);
        
        $maxRetries = 3;
        $retryCount = 0;
        
        while ($retryCount < $maxRetries) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->timeout(60)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $this->getSystemPrompt()
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'max_tokens' => $this->maxTokens,
                    'temperature' => 0.8,
                    'top_p' => 0.9,
                    'frequency_penalty' => 0.2,
                    'presence_penalty' => 0.1
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $catStory = $data['choices'][0]['message']['content'] ?? '';
                    
                    if (empty($catStory)) {
                        throw new Exception("OpenAI returned empty response");
                    }

                    return trim($catStory);
                } else {
                    throw new Exception("OpenAI API error: " . $response->body());
                }
                
            } catch (Exception $e) {
                $retryCount++;
                Log::warning("OpenAI API attempt {$retryCount} failed: " . $e->getMessage());
                
                if ($retryCount >= $maxRetries) {
                    throw $e;
                }
                
                sleep(2);
            }
        }
    }

    /**
     * Generate cat story for very large content using chunking
     */
    private function generateCatStoryForLargeContent(string $content): string
    {
        try {
            // Split content into manageable chunks
            $chunkSize = 15000; // 15KB chunks
            $chunks = str_split($content, $chunkSize);
            $chunkCount = count($chunks);
            
            Log::info("Processing large content in {$chunkCount} chunks");
            
            // Generate story for first few chunks and create a summary
            $maxChunks = min(5, $chunkCount); // Process max 5 chunks
            $chunkStories = [];
            
            for ($i = 0; $i < $maxChunks; $i++) {
                try {
                    $chunkPrompt = $this->buildCatStoryPromptForChunk($chunks[$i], $i + 1, $chunkCount);
                    
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->timeout(60)
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model' => $this->model,
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => $this->getSystemPrompt()
                            ],
                            [
                                'role' => 'user',
                                'content' => $chunkPrompt
                            ]
                        ],
                        'max_tokens' => 1000, // Smaller for chunks
                        'temperature' => 0.8,
                        'top_p' => 0.9,
                        'frequency_penalty' => 0.2,
                        'presence_penalty' => 0.1
                    ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        $chunkStory = $data['choices'][0]['message']['content'] ?? '';
                        if (!empty($chunkStory)) {
                            $chunkStories[] = trim($chunkStory);
                        }
                    }
                    
                    // Small delay between API calls
                    sleep(1);
                    
                } catch (Exception $e) {
                    Log::warning("Failed to process chunk " . ($i + 1) . ": " . $e->getMessage());
                    $chunkStories[] = "Kitty couldn't read this part properly, but kitty tried!";
                }
            }
            
            // Combine chunk stories
            $combinedStory = implode("\n\n", $chunkStories);
            
            // Add footer for very large documents
            if ($chunkCount > $maxChunks) {
                $remainingChunks = $chunkCount - $maxChunks;
                $combinedStory .= "\n\n[Kitty note: This document was very big! Kitty read the first parts for you. There were {$remainingChunks} more parts that kitty didn't have time to read, but kitty got the main ideas!]";
            }
            
            return $combinedStory;
            
        } catch (Exception $e) {
            Log::error("Large content processing failed: " . $e->getMessage());
            
            // Fallback: Use just the beginning of the content
            $shortContent = substr($content, 0, 20000); // First 20KB
            return $this->generateCatStoryForNormalContent($shortContent) . 
                   "\n\n[Kitty note: This document was very long! Kitty summarized the beginning for you.]";
        }
    }

    /**
     * Check if OpenAI service is available
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout(10)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Hello'
                    ]
                ],
                'max_tokens' => 5
            ]);

            return $response->successful();

        } catch (Exception $e) {
            Log::warning("OpenAI service availability check failed: " . $e->getMessage());
            return false;
        }
    }

    private function getSystemPrompt(): string
    {
        return "You are a simple, innocent cat who explains complex things in very basic language. You always speak as a cat using 'kitty' instead of 'I' and use simple words that a child could understand. You love fish, sleeping, and playing. You make everything sound like an adventure. Keep explanations short and fun. Always stay in character as a cute, slightly confused but enthusiastic cat.";
    }

    private function buildCatStoryPrompt(string $content): string
    {
        $maxContentLength = 20000; // 20KB
        if (strlen($content) > $maxContentLength) {
            $content = substr($content, 0, $maxContentLength) . "...";
        }

        return "Please rewrite this complex document into a simple story told by a cat character. Use very simple language, short sentences, and explain everything like you're a cat who doesn't fully understand but is trying to help. Make it entertaining and easy to understand. Here's the content to transform:\n\n" . $content . "\n\nRemember: Use 'kitty' instead of 'I', keep it simple, fun, and cat-like!";
    }

    private function buildCatStoryPromptForChunk(string $content, int $chunkNumber, int $totalChunks): string
    {
        return "This is part {$chunkNumber} of {$totalChunks} from a big document. Please rewrite this part into a simple cat story. Use very simple language and explain like a confused but helpful cat. Here's this part:\n\n" . $content . "\n\nRemember: Use 'kitty' instead of 'I', keep it simple and cat-like!";
    }

    public function estimateProcessingTime(string $content): int
    {
        $wordCount = str_word_count($content);
        $baseTime = max(30, min(600, ceil($wordCount / 50))); // 30 seconds to 10 minutes
        
        // Add extra time for very large content
        if (strlen($content) > 100000) {
            $baseTime += 300; // Add 5 minutes for large content
        }
        
        return $baseTime;
    }

    public function validateContent(string $content): array
    {
        $issues = [];
        
        if (empty(trim($content))) {
            $issues[] = "Content is empty";
        }
        
        if (strlen($content) < 10) { // Reduced minimum
            $issues[] = "Content is too short (minimum 10 characters)";
        }
        
        return [
            'valid' => empty($issues),
            'issues' => $issues
        ];
    }
}