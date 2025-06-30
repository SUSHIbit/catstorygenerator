<?php

namespace App\Console\Commands;

use App\Services\OpenAIService;
use Illuminate\Console\Command;

class SimpleTestCommand extends Command
{
    protected $signature = 'test:simple';
    protected $description = 'Simple test of core functionality (skips document processing)';

    public function handle(): int
    {
        $this->info("🔄 Running simple functionality tests...");

        // Test 1: Check environment
        $this->info("1️⃣ Checking environment...");
        
        if (!env('OPENAI_API_KEY')) {
            $this->error("❌ OPENAI_API_KEY not set in .env file");
            $this->info("   Add: OPENAI_API_KEY=your_key_here to your .env file");
            return 1;
        }
        $this->info("✅ OpenAI API key is configured");

        if (env('QUEUE_CONNECTION') !== 'sync') {
            $this->warn("⚠️  QUEUE_CONNECTION is not set to 'sync' - jobs may not run immediately");
            $this->info("   Add: QUEUE_CONNECTION=sync to your .env file for immediate processing");
        } else {
            $this->info("✅ Queue connection set to 'sync'");
        }

        // Test 2: Test OpenAI service
        $this->info("2️⃣ Testing OpenAI service...");
        
        try {
            $openAIService = app(OpenAIService::class);
            
            if ($openAIService->isAvailable()) {
                $this->info("✅ OpenAI service is available and responding");
            } else {
                $this->error("❌ OpenAI service is not available");
                $this->info("   Check your API key and internet connection");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("❌ OpenAI service test failed: " . $e->getMessage());
            return 1;
        }

        // Test 3: Test a simple cat story generation
        $this->info("3️⃣ Testing cat story generation with sample text...");
        
        try {
            $sampleContent = "This is a test document about financial investments. Investors should diversify their portfolio to minimize risk while maximizing returns. The stock market can be volatile, so it's important to research before making investment decisions.";
            
            // Create a mock document
            $mockDocument = new \App\Models\Document();
            $mockDocument->id = 999999; // Use a high ID that won't conflict
            $mockDocument->title = "Test Document";
            $mockDocument->original_content = $sampleContent;
            
            $catStory = $openAIService->generateCatStory($mockDocument);
            
            $this->info("✅ Cat story generated successfully!");
            $this->info("   Story length: " . strlen($catStory) . " characters");
            $this->line("📝 Generated story preview:");
            $this->line("   " . substr($catStory, 0, 200) . "...");
            
        } catch (\Exception $e) {
            $this->error("❌ Cat story generation failed: " . $e->getMessage());
            return 1;
        }

        $this->info("4️⃣ Checking database connection...");
        
        try {
            \DB::connection()->getPdo();
            $this->info("✅ Database connection successful");
            
            $userCount = \App\Models\User::count();
            $docCount = \App\Models\Document::count();
            $this->info("   Users: {$userCount}, Documents: {$docCount}");
            
        } catch (\Exception $e) {
            $this->error("❌ Database connection failed: " . $e->getMessage());
            return 1;
        }

        $this->info("🎉 All basic tests passed!");
        $this->line("");
        $this->info("📋 Next steps:");
        $this->info("   1. Try uploading a simple PDF or Word document through the web interface");
        $this->info("   2. Check the Laravel logs if anything fails: tail -f storage/logs/laravel.log");
        $this->info("   3. If PowerPoint files fail, that's a known issue with the PHPPresentation library");
        
        return 0;
    }
}