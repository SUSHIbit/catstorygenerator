<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Log;
use Exception;

class MockOpenAIService
{
    public function __construct()
    {
        Log::info("Using Mock OpenAI Service for testing");
    }

    /**
     * Mock cat story generation
     */
    public function generateCatStory(Document $document): string
    {
        Log::info("Generating mock cat story for document ID: {$document->id}");
        
        if (empty($document->original_content)) {
            throw new Exception("No content available to transform into cat story");
        }

        // Generate a simple mock cat story based on content length
        $contentLength = strlen($document->original_content);
        $wordCount = str_word_count($document->original_content);
        
        $mockStory = "🐱 Kitty's Story About: {$document->title}\n\n";
        
        if ($wordCount < 50) {
            $mockStory .= "Kitty found a small document! It was only {$wordCount} words long. Kitty thinks this document is about something simple, but kitty couldn't read all the big words. Kitty hopes it makes sense to you! 🐾";
        } elseif ($wordCount < 200) {
            $mockStory .= "Kitty read a medium-sized document with {$wordCount} words! Kitty thinks this document talks about important things. There were lots of words that kitty doesn't understand, but kitty tried really hard to make sense of it all. Kitty hopes the human finds this useful! 📚🐾";
        } else {
            $mockStory .= "Wow! Kitty found a BIG document with {$wordCount} words! That's a lot for kitty's little brain. 🧠\n\n";
            $mockStory .= "Kitty tried to read everything, but there were so many pages! Kitty thinks this document is very important because humans wrote so much about it. ";
            $mockStory .= "Kitty saw lots of complicated words that made kitty's head spin. 😵‍💫\n\n";
            $mockStory .= "But kitty is smart! Kitty thinks this document is probably about:\n";
            $mockStory .= "- Important business things that humans care about 💼\n";
            $mockStory .= "- Lots of numbers and charts (kitty likes the colorful charts!) 📊\n";
            $mockStory .= "- Maybe some rules or procedures (kitty doesn't like rules unless they're about treats) 🐟\n\n";
            $mockStory .= "Kitty recommends that humans read this carefully because it seems very serious. Kitty now needs a nap after reading so much! 😴🐾";
        }
        
        $mockStory .= "\n\n[This is a mock cat story generated for testing purposes. To get real AI-generated stories, configure your OpenAI API key properly and set OPENAI_MOCK=false in your .env file.]";
        
        Log::info("Generated mock cat story with " . strlen($mockStory) . " characters");
        
        return $mockStory;
    }

    /**
     * Mock availability check
     */
    public function isAvailable(): bool
    {
        Log::info("Mock OpenAI service is always available");
        return true;
    }

    public function validateContent(string $content): array
    {
        return [
            'valid' => !empty(trim($content)) && strlen($content) >= 10,
            'issues' => empty(trim($content)) ? ['Content is empty'] : []
        ];
    }

    public function estimateProcessingTime(string $content): int
    {
        return 30; // Mock: always 30 seconds
    }
}