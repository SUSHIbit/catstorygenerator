<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Log;
use Exception;

class MockOpenAIService
{
    public function __construct()
    {
        Log::info("Using Improved Mock OpenAI Service for testing");
    }

    /**
     * Generate realistic mock cat story based on actual content
     */
    public function generateCatStory(Document $document): string
    {
        Log::info("Generating improved mock cat story for document ID: {$document->id}");
        
        if (empty($document->original_content)) {
            throw new Exception("No content available to transform into cat story");
        }

        // Analyze the content to create a more realistic story
        $content = $document->original_content;
        $wordCount = str_word_count($content);
        $contentLength = strlen($content);
        
        // Extract some actual words/topics from the content
        $topics = $this->extractTopicsFromContent($content);
        $documentType = $this->guessDocumentType($content, $document->title);
        
        $mockStory = "🐱 Kitty's Story About: {$document->title}\n\n";
        
        // Generate story based on content analysis
        if ($wordCount < 100) {
            $mockStory .= $this->generateShortContentStory($topics, $documentType, $wordCount);
        } elseif ($wordCount < 500) {
            $mockStory .= $this->generateMediumContentStory($topics, $documentType, $wordCount);
        } elseif ($wordCount < 2000) {
            $mockStory .= $this->generateLongContentStory($topics, $documentType, $wordCount);
        } else {
            $mockStory .= $this->generateVeryLongContentStory($topics, $documentType, $wordCount, $contentLength);
        }
        
        $mockStory .= "\n\n[This is a mock cat story generated for testing purposes. To get real AI-generated stories, configure your OpenAI API key properly and set OPENAI_MOCK=false in your .env file.]";
        
        Log::info("Generated improved mock cat story with " . strlen($mockStory) . " characters");
        
        return $mockStory;
    }

    /**
     * Extract key topics from content
     */
    private function extractTopicsFromContent(string $content): array
    {
        // Simple topic extraction - get common meaningful words
        $words = str_word_count(strtolower($content), 1);
        $commonWords = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'a', 'an', 'is', 'are', 'was', 'were', 'be', 'been', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'can', 'this', 'that', 'these', 'those', 'it', 'they', 'we', 'you', 'he', 'she', 'him', 'her', 'his', 'their', 'our', 'your', 'my', 'me', 'us', 'them'];
        
        $wordCounts = array_count_values($words);
        $meaningfulWords = array_diff_key($wordCounts, array_flip($commonWords));
        arsort($meaningfulWords);
        
        return array_slice(array_keys($meaningfulWords), 0, 8); // Top 8 topics
    }

    /**
     * Guess document type from content and title
     */
    private function guessDocumentType(string $content, string $title): string
    {
        $content_lower = strtolower($content . ' ' . $title);
        
        if (preg_match('/\b(financial|money|budget|investment|revenue|profit|cost|expense|accounting|finance)\b/', $content_lower)) {
            return 'financial';
        } elseif (preg_match('/\b(marketing|sales|customer|brand|advertising|promotion|campaign)\b/', $content_lower)) {
            return 'marketing';
        } elseif (preg_match('/\b(technical|software|system|development|programming|code|technology)\b/', $content_lower)) {
            return 'technical';
        } elseif (preg_match('/\b(research|study|analysis|data|results|findings|methodology)\b/', $content_lower)) {
            return 'research';
        } elseif (preg_match('/\b(policy|procedure|guidelines|rules|compliance|legal)\b/', $content_lower)) {
            return 'policy';
        } elseif (preg_match('/\b(training|education|learning|course|lesson|instruction)\b/', $content_lower)) {
            return 'educational';
        } elseif (preg_match('/\b(project|plan|strategy|goals|objectives|timeline)\b/', $content_lower)) {
            return 'planning';
        } else {
            return 'general';
        }
    }

    /**
     * Generate story for short content
     */
    private function generateShortContentStory(array $topics, string $type, int $wordCount): string
    {
        $topicText = !empty($topics) ? " about " . implode(", ", array_slice($topics, 0, 3)) : "";
        
        return "Kitty found a small document{$topicText}! It was only {$wordCount} words long, which is perfect for kitty's attention span! 🐱\n\n" .
               "Kitty read through it carefully and thinks this document explains some important things. Even though it's short, kitty learned some new concepts that humans seem to care about.\n\n" .
               "Kitty especially noticed these interesting words: " . implode(", ", array_slice($topics, 0, 4)) . ". Kitty isn't sure what all of them mean, but they sound very important to humans!\n\n" .
               "The document is easy to understand because it's not too long. Kitty recommends this for humans who don't like reading big scary documents! 📚🐾";
    }

    /**
     * Generate story for medium content
     */
    private function generateMediumContentStory(array $topics, string $type, int $wordCount): string
    {
        $typeDescriptions = [
            'financial' => "money and numbers",
            'marketing' => "selling things and making customers happy",
            'technical' => "computers and technical stuff",
            'research' => "studying things very carefully",
            'policy' => "rules and procedures",
            'educational' => "teaching and learning",
            'planning' => "making plans and strategies",
            'general' => "various important topics"
        ];
        
        $typeDesc = $typeDescriptions[$type] ?? "important business stuff";
        $topicList = implode(", ", array_slice($topics, 0, 5));
        
        return "Kitty found a medium-sized document with {$wordCount} words about {$typeDesc}! This was a good size for kitty - not too short, not too long. 📖🐱\n\n" .
               "The document talks about several interesting topics including: {$topicList}. Kitty tried really hard to understand all these concepts!\n\n" .
               "Here's what kitty learned:\n" .
               "• The document explains how humans handle {$typeDesc}\n" .
               "• There are some important rules and procedures involved\n" .
               "• Kitty noticed lots of specific details that seem very important\n" .
               "• The humans who wrote this clearly know what they're talking about!\n\n" .
               "Kitty thinks this document would be helpful for humans who need to understand {$typeDesc}. It's detailed enough to be useful but not so long that it makes kitty sleepy! 😴\n\n" .
               "Kitty recommends reading this carefully because there's good information here! 🐾";
    }

    /**
     * Generate story for long content
     */
    private function generateLongContentStory(array $topics, string $type, int $wordCount): string
    {
        $topicList = implode(", ", array_slice($topics, 0, 6));
        
        return "Wow! Kitty found a pretty big document with {$wordCount} words! That's a lot for kitty's little brain to process, but kitty gave it kitty's best effort! 🧠🐱\n\n" .
               "This document covers many important topics including: {$topicList}. Kitty was impressed by how much information the humans packed into this!\n\n" .
               "Kitty's Summary of the Main Ideas:\n\n" .
               "📝 **What kitty learned:** The document explains complex concepts step by step. There are different sections that each focus on specific aspects of the topic.\n\n" .
               "🔍 **Important details:** Kitty noticed there are specific procedures, guidelines, and examples throughout the document. The humans really thought this through!\n\n" .
               "💡 **Kitty's thoughts:** This seems like a comprehensive guide that covers everything someone would need to know about the subject. It's well-organized and detailed.\n\n" .
               "⚠️ **Kitty's warning:** This is definitely a document that humans should read carefully and maybe take notes on. There's too much good information to remember all at once!\n\n" .
               "Kitty thinks this document is very valuable for humans who need to understand these topics thoroughly. Just make sure to read it when you're not distracted by cat videos! 📚🐾";
    }

    /**
     * Generate story for very long content
     */
    private function generateVeryLongContentStory(array $topics, string $type, int $wordCount, int $contentLength): string
    {
        $topicList = implode(", ", array_slice($topics, 0, 8));
        $fileSizeMB = round($contentLength / (1024 * 1024), 1);
        
        return "OH MY WHISKERS! 😻 Kitty found a HUGE document with {$wordCount} words and " . number_format($contentLength) . " characters! That's like {$fileSizeMB}MB of pure information! Kitty's eyes got very wide looking at all this content!\n\n" .
               "This massive document covers SO many topics including: {$topicList}, and probably many more that kitty couldn't keep track of!\n\n" .
               "🐱 **Kitty's Reading Adventure:**\n" .
               "Kitty started reading from the beginning and quickly realized this is a VERY comprehensive document. There are multiple sections, subsections, and probably sub-sub-sections!\n\n" .
               "📚 **What kitty discovered:**\n" .
               "• This document is like a complete guide or manual about the subject\n" .
               "• There are detailed explanations, examples, and step-by-step instructions\n" .
               "• Kitty found charts, data, procedures, and lots of important information\n" .
               "• The humans who wrote this were very thorough - maybe TOO thorough for kitty!\n\n" .
               "💭 **Kitty's honest opinion:**\n" .
               "This document contains EVERYTHING someone would need to know about the topic. It's incredibly detailed and comprehensive. Kitty is impressed but also a little overwhelmed!\n\n" .
               "🎯 **Kitty's recommendation:**\n" .
               "This is definitely a reference document that humans should:\n" .
               "• Read in sections, not all at once\n" .
               "• Take breaks (maybe for cat naps?)\n" .
               "• Use as a reference guide\n" .
               "• Share with other humans who need this information\n\n" .
               "Kitty thinks this document is like a treasure chest of information, but humans should pace themselves when reading it. Even kitty needed several nap breaks! 😴🐾\n\n" .
               "**Final kitty verdict:** Very valuable, very comprehensive, very... BIG! 📖✨";
    }

    /**
     * Mock availability check
     */
    public function isAvailable(): bool
    {
        Log::info("Improved Mock OpenAI service is always available");
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
        $contentLength = strlen($content);
        
        if ($contentLength < 1000) return 15; // Very small
        if ($contentLength < 10000) return 30; // Small
        if ($contentLength < 50000) return 45; // Medium
        if ($contentLength < 100000) return 60; // Large
        return 90; // Very large
    }
}