<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Services\OpenAIService;
use App\Jobs\GenerateCatStoryJob;
use Illuminate\Console\Command;

class TestAICommand extends Command
{
    protected $signature = 'ai:test {document_id? : Document ID to test} {--check : Just check if AI service is available}';
    protected $description = 'Test AI integration and cat story generation';

    public function handle(OpenAIService $openAIService): int
    {
        if ($this->option('check')) {
            return $this->checkAIService($openAIService);
        }

        $documentId = $this->argument('document_id');
        
        if ($documentId) {
            return $this->testSpecificDocument($documentId, $openAIService);
        }

        return $this->testRandomDocument($openAIService);
    }

    private function checkAIService(OpenAIService $openAIService): int
    {
        $this->info('🔍 Checking AI service availability...');
        
        if ($openAIService->isAvailable()) {
            $this->info('✅ AI service is available and working!');
            return 0;
        } else {
            $this->error('❌ AI service is not available. Check your OpenAI API key and configuration.');
            return 1;
        }
    }

    private function testSpecificDocument(int $documentId, OpenAIService $openAIService): int
    {
        $document = Document::find($documentId);

        if (!$document) {
            $this->error("❌ Document with ID {$documentId} not found.");
            return 1;
        }

        $this->info("🐱 Testing cat story generation for document: {$document->title}");
        
        if (!$document->original_content) {
            $this->error("❌ Document has no extracted content to work with.");
            return 1;
        }

        if ($document->hasStory()) {
            $this->warn("⚠️  Document already has a cat story. Generating a new one...");
        }

        // Validate content
        $validation = $openAIService->validateContent($document->original_content);
        if (!$validation['valid']) {
            $this->error("❌ Content validation failed: " . implode(', ', $validation['issues']));
            return 1;
        }

        $this->info("📊 Content stats:");
        $this->line("   - Length: " . number_format(strlen($document->original_content)) . " characters");
        $this->line("   - Words: " . number_format(str_word_count($document->original_content)));
        $this->line("   - Estimated processing time: " . $openAIService->estimateProcessingTime($document->original_content) . " seconds");

        try {
            $this->info("🚀 Generating cat story...");
            $catStory = $openAIService->generateCatStory($document);
            
            $this->info("✅ Cat story generated successfully!");
            $this->line("📝 Story preview (first 200 characters):");
            $this->line("   " . Str::limit($catStory, 200));
            
            // Ask if user wants to save the story
            if ($this->confirm('Save this cat story to the document?', true)) {
                $document->markAsCompleted($catStory);
                $this->info("💾 Cat story saved to document!");
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Cat story generation failed: " . $e->getMessage());
            return 1;
        }
    }

    private function testRandomDocument(OpenAIService $openAIService): int
    {
        $this->info('🔍 Looking for a document with content to test...');

        $document = Document::whereNotNull('original_content')
            ->whereNull('cat_story')
            ->inRandomOrder()
            ->first();

        if (!$document) {
            $this->warn('⚠️  No suitable documents found. Looking for any document with content...');
            
            $document = Document::whereNotNull('original_content')
                ->inRandomOrder()
                ->first();
        }

        if (!$document) {
            $this->error('❌ No documents with extracted content found. Please upload and process a document first.');
            return 1;
        }

        $this->info("📄 Found document: {$document->title} (ID: {$document->id})");
        
        return $this->testSpecificDocument($document->id, $openAIService);
    }
}