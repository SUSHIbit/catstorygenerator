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
        
        // Don't throw exception in constructor, just log warning
        if (empty($this->apiKey)) {
            Log::warning('OpenAI API key not configured. Cat story generation will fail.');
        }
    }

    /**
     * Generate a cat story from document content - HANDLES LARGE CONTENT
     */
    public function generateCatStory(Document $document): string
    {
        try {
            Log::info("Starting cat story generation for document ID: {$document->id}");

            // Check API key
            if (empty($this->apiKey)) {
                throw new Exception("OpenAI API key not configured. Please set OPENAI_API_KEY in your .env file.");
            }

            if (empty($document->original_content)) {
                throw new Exception("No content available to transform into cat story");
            }

            // Handle very large content by chunking
            $content = $document->original_content;
            $contentLength = strlen($content);
            
            Log::info("Processing {$contentLength} characters for cat story generation");
            
            // If content is very large, use summary approach
            if ($contentLength > 50000) { // 50KB
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
                Log::info("Attempting OpenAI API call (attempt " . ($retryCount + 1) . ")");
                
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->timeout(120) // Increased timeout
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

                Log::info("OpenAI API response status: " . $response->status());

                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (!isset($data['choices'][0]['message']['content'])) {
                        throw new Exception("Invalid response structure from OpenAI: " . json_encode($data));
                    }
                    
                    $catStory = $data['choices'][0]['message']['content'] ?? '';
                    
                    if (empty($catStory)) {
                        throw new Exception("OpenAI returned empty response");
                    }

                    Log::info("Successfully generated cat story (" . strlen($catStory) . " characters)");
                    return trim($catStory);
                } else {
                    $errorBody = $response->body();
                    Log::error("OpenAI API error response: " . $errorBody);
                    throw new Exception("OpenAI API error (HTTP {$response->status()}): " . $errorBody);
                }
                
            } catch (Exception $e) {
                $retryCount++;
                Log::warning("OpenAI API attempt {$retryCount} failed: " . $e->getMessage());
                
                if ($retryCount >= $maxRetries) {
                    throw new Exception("OpenAI API failed after {$maxRetries} attempts. Last error: " . $e->getMessage());
                }
                
                // Wait before retrying
                sleep(pow(2, $retryCount)); // Exponential backoff: 2s, 4s, 8s
            }
        }
        
        throw new Exception("OpenAI API failed after all retries");
    }

    /**
     * Generate cat story for very large content using chunking
     */
    private function generateCatStoryForLargeContent(string $content): string
    {
        try {
            // Split content into manageable chunks
            $chunkSize = 10000; // 10KB chunks - smaller for reliability
            $chunks = str_split($content, $chunkSize);
            $chunkCount = count($chunks);
            
            Log::info("Processing large content in {$chunkCount} chunks");
            
            // Generate story for first few chunks
            $maxChunks = min(3, $chunkCount); // Process max 3 chunks
            $chunkStories = [];
            
            for ($i = 0; $i < $maxChunks; $i++) {
                try {
                    Log::info("Processing chunk " . ($i + 1) . " of {$maxChunks}");
                    
                    $chunkPrompt = $this->buildCatStoryPromptForChunk($chunks[$i], $i + 1, $chunkCount);
                    
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->timeout(120)
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
                        'max_tokens' => 800, // Smaller for chunks
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
                    } else {
                        Log::warning("Failed to process chunk " . ($i + 1) . ": HTTP " . $response->status());
                        $chunkStories[] = "Kitty couldn't read this part properly, but kitty tried!";
                    }
                    
                    // Small delay between API calls
                    if ($i < $maxChunks - 1) {
                        sleep(1);
                    }
                    
                } catch (Exception $e) {
                    Log::warning("Failed to process chunk " . ($i + 1) . ": " . $e->getMessage());
                    $chunkStories[] = "Kitty had trouble with this part, but kitty kept trying!";
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
            $shortContent = substr($content, 0, 15000); // First 15KB
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
            if (empty($this->apiKey)) {
                Log::warning("OpenAI API key not configured");
                return false;
            }

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
                        'content' => 'Test'
                    ]
                ],
                'max_tokens' => 5
            ]);

            $isAvailable = $response->successful();
            Log::info("OpenAI service availability check: " . ($isAvailable ? 'Available' : 'Unavailable'));
            
            return $isAvailable;

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
        $maxContentLength = 15000; // 15KB - reduced for better reliability
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
        if (strlen($content) > 50000) {
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
        
        if (strlen($content) > 200000) { // 200KB limit
            $issues[] = "Content is very large and may take longer to process";
        }
        
        return [
            'valid' => empty($issues),
            'issues' => $issues
        ];
    }
}