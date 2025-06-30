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
        $this->maxTokens = 3000; // Increased for better stories
        $this->model = 'gpt-3.5-turbo';
        
        if (empty($this->apiKey)) {
            Log::warning('OpenAI API key not configured. Cat story generation will fail.');
        }
    }

    /**
     * Generate a cat story from document content - IMPROVED VERSION
     */
    public function generateCatStory(Document $document): string
    {
        try {
            Log::info("Starting IMPROVED cat story generation for document ID: {$document->id}");

            if (empty($this->apiKey)) {
                throw new Exception("OpenAI API key not configured. Please set OPENAI_API_KEY in your .env file.");
            }

            if (empty($document->original_content)) {
                throw new Exception("No content available to transform into cat story");
            }

            $content = $document->original_content;
            $contentLength = strlen($content);
            
            Log::info("Processing {$contentLength} characters for IMPROVED cat story generation");
            
            // Use intelligent content processing based on size
            if ($contentLength > 80000) { // Very large content (80KB+)
                return $this->generateCatStoryForVeryLargeContent($content, $document->title);
            } elseif ($contentLength > 40000) { // Large content (40KB+)
                return $this->generateCatStoryForLargeContent($content, $document->title);
            } else {
                return $this->generateCatStoryForNormalContent($content, $document->title);
            }

        } catch (Exception $e) {
            Log::error("IMPROVED cat story generation failed for document ID: {$document->id}. Error: " . $e->getMessage());
            throw new Exception("Failed to generate cat story: " . $e->getMessage());
        }
    }

    /**
     * Generate cat story for normal-sized content (under 40KB)
     */
    private function generateCatStoryForNormalContent(string $content, string $title): string
    {
        $prompt = $this->buildImprovedCatStoryPrompt($content, $title);
        
        return $this->callOpenAIWithRetry($prompt, 3000);
    }

    /**
     * Generate cat story for large content (40KB-80KB) using smart summarization
     */
    private function generateCatStoryForLargeContent(string $content, string $title): string
    {
        try {
            Log::info("Processing large content using smart summarization approach");
            
            // First, create a comprehensive summary
            $summaryPrompt = $this->buildContentSummaryPrompt($content, $title);
            $summary = $this->callOpenAIWithRetry($summaryPrompt, 2000);
            
            // Then create a cat story from the summary
            $storyPrompt = $this->buildCatStoryFromSummaryPrompt($summary, $title);
            $catStory = $this->callOpenAIWithRetry($storyPrompt, 3000);
            
            // Add a note about the processing method
            $note = "\n\n[Kitty note: This was a big document with " . number_format(strlen($content)) . " characters! Kitty read it all and made this story from the important parts. If kitty missed anything important, kitty is sorry! 🐱]";
            
            return $catStory . $note;
            
        } catch (Exception $e) {
            Log::error("Large content processing failed: " . $e->getMessage());
            
            // Fallback: Use first portion of content
            $shortContent = substr($content, 0, 30000); // First 30KB
            return $this->generateCatStoryForNormalContent($shortContent, $title) . 
                   "\n\n[Kitty note: This document was very long! Kitty focused on the beginning parts but there was more content that kitty didn't have time to include. 🐱]";
        }
    }

    /**
     * Generate cat story for very large content (80KB+) using multi-stage approach
     */
    private function generateCatStoryForVeryLargeContent(string $content, string $title): string
    {
        try {
            Log::info("Processing very large content using multi-stage approach");
            
            // Split content into meaningful sections
            $sections = $this->splitContentIntoSections($content, 20000); // 20KB sections
            $sectionSummaries = [];
            
            // Process first 3 sections to avoid too many API calls
            $sectionsToProcess = min(3, count($sections));
            
            for ($i = 0; $i < $sectionsToProcess; $i++) {
                try {
                    $sectionPrompt = $this->buildSectionSummaryPrompt($sections[$i], $i + 1, $title);
                    $summary = $this->callOpenAIWithRetry($sectionPrompt, 1500);
                    $sectionSummaries[] = $summary;
                    
                    // Small delay between API calls
                    if ($i < $sectionsToProcess - 1) {
                        sleep(1);
                    }
                } catch (Exception $e) {
                    Log::warning("Failed to process section " . ($i + 1) . ": " . $e->getMessage());
                    $sectionSummaries[] = "Section " . ($i + 1) . " was too complex for kitty to understand properly.";
                }
            }
            
            // Combine summaries into final cat story
            $combinedSummary = implode("\n\n", $sectionSummaries);
            $finalPrompt = $this->buildFinalCatStoryPrompt($combinedSummary, $title, count($sections));
            $catStory = $this->callOpenAIWithRetry($finalPrompt, 3000);
            
            // Add processing note
            $processedSections = $sectionsToProcess;
            $totalSections = count($sections);
            $note = "\n\n[Kitty note: This was a HUGE document with " . number_format(strlen($content)) . " characters split into {$totalSections} sections! Kitty read the first {$processedSections} sections very carefully. There might be more content that kitty didn't get to, but kitty captured the main ideas! 🐱📚]";
            
            return $catStory . $note;
            
        } catch (Exception $e) {
            Log::error("Very large content processing failed: " . $e->getMessage());
            
            // Final fallback
            return $this->generateCatStoryForLargeContent($content, $title);
        }
    }

    /**
     * Split content into logical sections
     */
    private function splitContentIntoSections(string $content, int $maxSectionSize): array
    {
        $sections = [];
        $paragraphs = preg_split('/\n\s*\n/', $content);
        $currentSection = '';
        
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (empty($paragraph)) continue;
            
            if (strlen($currentSection . $paragraph) > $maxSectionSize && !empty($currentSection)) {
                $sections[] = trim($currentSection);
                $currentSection = $paragraph;
            } else {
                $currentSection .= ($currentSection ? "\n\n" : '') . $paragraph;
            }
        }
        
        if (!empty($currentSection)) {
            $sections[] = trim($currentSection);
        }
        
        return $sections;
    }

    /**
     * Call OpenAI API with retry logic
     */
    private function callOpenAIWithRetry(string $prompt, int $maxTokens, int $maxRetries = 3): string
    {
        $retryCount = 0;
        
        while ($retryCount < $maxRetries) {
            try {
                Log::info("OpenAI API call attempt " . ($retryCount + 1) . " (max tokens: {$maxTokens})");
                
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->timeout(180) // Increased timeout for better reliability
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $this->getImprovedSystemPrompt()
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'max_tokens' => $maxTokens,
                    'temperature' => 0.9, // More creative
                    'top_p' => 0.9,
                    'frequency_penalty' => 0.3,
                    'presence_penalty' => 0.2
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (!isset($data['choices'][0]['message']['content'])) {
                        throw new Exception("Invalid response structure from OpenAI: " . json_encode($data));
                    }
                    
                    $result = trim($data['choices'][0]['message']['content']);
                    
                    if (empty($result)) {
                        throw new Exception("OpenAI returned empty response");
                    }

                    Log::info("Successfully generated content (" . strlen($result) . " characters)");
                    return $result;
                } else {
                    $errorBody = $response->body();
                    throw new Exception("OpenAI API error (HTTP {$response->status()}): " . $errorBody);
                }
                
            } catch (Exception $e) {
                $retryCount++;
                Log::warning("OpenAI API attempt {$retryCount} failed: " . $e->getMessage());
                
                if ($retryCount >= $maxRetries) {
                    throw new Exception("OpenAI API failed after {$maxRetries} attempts. Last error: " . $e->getMessage());
                }
                
                // Exponential backoff
                sleep(pow(2, $retryCount));
            }
        }
        
        throw new Exception("OpenAI API failed after all retries");
    }

    /**
     * Improved system prompt for better cat personalities
     */
    private function getImprovedSystemPrompt(): string
    {
        return "You are a helpful, curious cat who loves to explain things in simple, fun ways. You always speak as 'kitty' (never 'I') and use simple language that anyone can understand. You're enthusiastic about learning and teaching, but sometimes get distracted by cat things. You make complex topics easy and fun by relating them to everyday experiences, especially cat experiences. You're patient, kind, and never condescending. You love to use simple analogies and examples. Stay in character as a friendly, intelligent cat throughout the entire response.";
    }

    /**
     * Build improved cat story prompt with better structure
     */
    private function buildImprovedCatStoryPrompt(string $content, string $title): string
    {
        // Limit content to prevent token overflow
        $maxContentLength = 25000; // 25KB for better processing
        if (strlen($content) > $maxContentLength) {
            $content = substr($content, 0, $maxContentLength) . "...";
        }

        return "Kitty needs to explain this document called '{$title}' in a fun, simple way that anyone can understand!

IMPORTANT INSTRUCTIONS FOR KITTY:
1. Read the ENTIRE content carefully
2. Identify the main topics, concepts, and key points
3. Explain everything in simple cat language using 'kitty' instead of 'I'
4. Make it educational but fun and engaging
5. Use analogies that relate to everyday life (especially cat life!)
6. Keep the important information but make it easy to understand
7. Be enthusiastic and curious about the content
8. If there are complex terms, explain them simply
9. Make sure the story actually covers the main content - don't just give a generic response!

DOCUMENT CONTENT TO TRANSFORM:
{$content}

Please create a comprehensive cat story that covers the main points from this document. Make sure kitty explains the actual content, not just talks about reading documents in general!";
    }

    /**
     * Build content summary prompt for large documents
     */
    private function buildContentSummaryPrompt(string $content, string $title): string
    {
        $maxContentLength = 30000; // 30KB for summary
        if (strlen($content) > $maxContentLength) {
            $content = substr($content, 0, $maxContentLength) . "...";
        }

        return "Please create a comprehensive summary of this document called '{$title}'. Identify all the main topics, key concepts, important details, and conclusions. Organize the information logically and preserve the essential meaning and structure.

DOCUMENT CONTENT:
{$content}

Create a detailed summary that captures all the important information while being well-organized and clear.";
    }

    /**
     * Build cat story from summary prompt
     */
    private function buildCatStoryFromSummaryPrompt(string $summary, string $title): string
    {
        return "Now kitty needs to turn this summary of '{$title}' into a fun, educational cat story! 

SUMMARY TO TRANSFORM:
{$summary}

Transform this summary into an engaging cat story where kitty explains all the concepts in simple, fun language. Make sure to cover all the main points from the summary but in a way that's easy and enjoyable to understand. Use cat analogies and everyday examples!";
    }

    /**
     * Build section summary prompt for very large documents
     */
    private function buildSectionSummaryPrompt(string $section, int $sectionNumber, string $title): string
    {
        return "This is section {$sectionNumber} from a document called '{$title}'. Please summarize the key points and main concepts from this section:

SECTION CONTENT:
{$section}

Provide a clear, concise summary of this section's main ideas and important details.";
    }

    /**
     * Build final cat story prompt for very large documents
     */
    private function buildFinalCatStoryPrompt(string $combinedSummary, string $title, int $totalSections): string
    {
        return "Kitty has summaries from {$totalSections} sections of a big document called '{$title}'. Now kitty needs to create one cohesive, fun cat story that explains everything!

SECTION SUMMARIES:
{$combinedSummary}

Create a comprehensive cat story that combines all these summaries into one engaging explanation. Make sure kitty covers all the main topics and concepts in simple, fun language with cat analogies and examples!";
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

    public function estimateProcessingTime(string $content): int
    {
        $wordCount = str_word_count($content);
        $contentLength = strlen($content);
        
        // More accurate estimates based on new processing logic
        if ($contentLength < 10000) return 45; // Small docs: 45 seconds
        if ($contentLength < 40000) return 90; // Medium docs: 1.5 minutes
        if ($contentLength < 80000) return 180; // Large docs: 3 minutes
        return 300; // Very large docs: 5 minutes
    }

    public function validateContent(string $content): array
    {
        $issues = [];
        
        if (empty(trim($content))) {
            $issues[] = "Content is empty";
        }
        
        if (strlen($content) < 10) {
            $issues[] = "Content is too short (minimum 10 characters)";
        }
        
        if (strlen($content) > 500000) { // 500KB limit
            $issues[] = "Content is extremely large and may take a very long time to process";
        }
        
        // Check for meaningful content
        $wordCount = str_word_count($content);
        if ($wordCount < 5) {
            $issues[] = "Content doesn't contain enough meaningful words";
        }
        
        return [
            'valid' => empty($issues),
            'issues' => $issues
        ];
    }
}