<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Services\DocumentParserService;
use App\Services\OpenAIService;
use Illuminate\Console\Command;

class TestProcessingCommand extends Command
{
    protected $signature = 'test:processing {document_id?}';
    protected $description = 'Test document processing end-to-end';

    public function handle(): int
    {
        $documentId = $this->argument('document_id');
        
        if ($documentId) {
            $document = Document::find($documentId);
            if (!$document) {
                $this->error("Document with ID {$documentId} not found.");
                return 1;
            }
        } else {
            $document = Document::latest()->first();
            if (!$document) {
                $this->error("No documents found in database.");
                return 1;
            }
        }

        $this->info("Testing processing for document: {$document->title} (ID: {$document->id})");

        // Test 1: Check file exists
        $filePath = storage_path('app/public/' . $document->filepath);
        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }
        $this->info("✅ File exists: {$filePath}");

        // Test 2: Test document parsing
        $parserService = app(DocumentParserService::class);
        $this->info("🔄 Testing document parsing for {$document->file_type} file...");
        
        try {
            $success = $parserService->parseDocument($document);
            if ($success) {
                $document->refresh();
                $contentLength = strlen($document->original_content ?? '');
                $this->info("✅ Document parsing successful");
                $this->info("   Content length: {$contentLength} characters");
                
                if ($contentLength < 20) {
                    $this->warn("⚠️  Warning: Very little text was extracted. This might be a visual-heavy document.");
                }
            } else {
                $this->error("❌ Document parsing failed");
                $this->info("   Check the document format and ensure it contains extractable text.");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("❌ Document parsing threw exception: " . $e->getMessage());
            $this->info("   This might be due to:");
            $this->info("   - Corrupted or password-protected file");
            $this->info("   - Unsupported file format variant");
            $this->info("   - File contains only images/graphics");
            return 1;
        }

        // Test 3: Test OpenAI service
        $openAIService = app(OpenAIService::class);
        $this->info("🔄 Testing OpenAI service...");
        
        if (!$openAIService->isAvailable()) {
            $this->error("❌ OpenAI service not available. Check your API key.");
            return 1;
        }
        $this->info("✅ OpenAI service is available");

        // Test 4: Test cat story generation
        $document->refresh();
        if ($document->original_content) {
            $this->info("🔄 Testing cat story generation...");
            try {
                $catStory = $openAIService->generateCatStory($document);
                $this->info("✅ Cat story generated successfully");
                $this->info("   Story length: " . strlen($catStory) . " characters");
                $this->line("📝 Story preview: " . substr($catStory, 0, 200) . "...");
                
                // Save the story
                $document->markAsCompleted($catStory);
                $this->info("💾 Story saved to document");
            } catch (\Exception $e) {
                $this->error("❌ Cat story generation failed: " . $e->getMessage());
                return 1;
            }
        }

        $this->info("🎉 All tests passed! Document processing is working correctly.");
        return 0;
    }
}