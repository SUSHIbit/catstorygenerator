<?php

namespace App\Services;

use App\Models\Document;
use OpenAI\Client;
use Illuminate\Support\Facades\Log;
use Exception;

class OpenAIService
{
    private Client $client;
    private int $maxTokens;
    private string $model;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->maxTokens = 2000;
        $this->model = 'gpt-3.5-turbo';
    }

    /**
     * Generate a cat story from document content
     */
    public function generateCatStory(Document $document): string
    {
        try {
            Log::info("Starting cat story generation for document ID: {$document->id}");

            if (empty($document->original_content)) {
                throw new Exception("No content available to transform into cat story");
            }

            $prompt = $this->buildCatStoryPrompt($document->original_content);
            
            // Add retry logic with better error handling
            $maxRetries = 3;
            $retryCount = 0;
            
            while ($retryCount < $maxRetries) {
                try {
                    $response = $this->client->chat()->create([
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

                    $catStory = $response->choices[0]->message->content ?? '';
                    
                    if (empty($catStory)) {
                        throw new Exception("OpenAI returned empty response");
                    }

                    Log::info("Cat story generated successfully for document ID: {$document->id}");
                    return trim($catStory);
                    
                } catch (Exception $e) {
                    $retryCount++;
                    Log::warning("OpenAI API attempt {$retryCount} failed: " . $e->getMessage());
                    
                    if ($retryCount >= $maxRetries) {
                        throw $e;
                    }
                    
                    // Wait before retry
                    sleep(2);
                }
            }

        } catch (Exception $e) {
            Log::error("OpenAI cat story generation failed for document ID: {$document->id}. Error: " . $e->getMessage());
            throw new Exception("Failed to generate cat story: " . $e->getMessage());
        }
    }

    /**
     * Check if OpenAI service is available with SSL handling
     */
    public function isAvailable(): bool
    {
        try {
            // Simple test with minimal content
            $response = $this->client->chat()->create([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Hello'
                    ]
                ],
                'max_tokens' => 5
            ]);

            return !empty($response->choices);

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
        $maxContentLength = 3000;
        if (strlen($content) > $maxContentLength) {
            $content = substr($content, 0, $maxContentLength) . "...";
        }

        return "Please rewrite this complex document into a simple story told by a cat character. Use very simple language, short sentences, and explain everything like you're a cat who doesn't fully understand but is trying to help. Make it entertaining and easy to understand. Here's the content to transform:\n\n" . $content . "\n\nRemember: Use 'kitty' instead of 'I', keep it simple, fun, and cat-like!";
    }

    public function estimateProcessingTime(string $content): int
    {
        $wordCount = str_word_count($content);
        return max(10, min(300, ceil($wordCount / 50)));
    }

    public function validateContent(string $content): array
    {
        $issues = [];
        
        if (empty(trim($content))) {
            $issues[] = "Content is empty";
        }
        
        if (strlen($content) < 50) {
            $issues[] = "Content is too short (minimum 50 characters)";
        }
        
        if (strlen($content) > 50000) {
            $issues[] = "Content is too long (maximum 50,000 characters)";
        }
        
        return [
            'valid' => empty($issues),
            'issues' => $issues
        ];
    }
}