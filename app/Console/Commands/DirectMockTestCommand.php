<?php

namespace App\Console\Commands;

use App\Services\MockOpenAIService;
use Illuminate\Console\Command;

class DirectMockTestCommand extends Command
{
    protected $signature = 'test:mock-direct';
    protected $description = 'Direct test of mock OpenAI service';

    public function handle(): int
    {
        $this->info("🔄 Testing mock OpenAI service directly...");

        // Test 1: Check environment
        $mockEnabled = env('OPENAI_MOCK', false);
        $this->info("1️⃣ OPENAI_MOCK setting: " . ($mockEnabled ? 'true' : 'false'));
        
        if (!$mockEnabled) {
            $this->warn("⚠️  OPENAI_MOCK is not set to true in .env file");
            $this->info("   Your .env should have: OPENAI_MOCK=true");
        }

        // Test 2: Create mock service directly
        $this->info("2️⃣ Creating mock service directly...");
        
        try {
            $mockService = new MockOpenAIService();
            $this->info("✅ Mock service created successfully");
        } catch (\Exception $e) {
            $this->error("❌ Failed to create mock service: " . $e->getMessage());
            return 1;
        }

        // Test 3: Test availability
        $this->info("3️⃣ Testing service availability...");
        
        try {
            $isAvailable = $mockService->isAvailable();
            if ($isAvailable) {
                $this->info("✅ Mock service is available");
            } else {
                $this->error("❌ Mock service reports as unavailable");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("❌ Availability test failed: " . $e->getMessage());
            return 1;
        }

        // Test 4: Test mock story generation
        $this->info("4️⃣ Testing mock story generation...");
        
        try {
            // Create a mock document
            $mockDocument = new \App\Models\Document();
            $mockDocument->id = 999999;
            $mockDocument->title = "Test Document";
            $mockDocument->original_content = "This is a test document about financial investments and portfolio management. Investors should consider diversifying their holdings across different asset classes to minimize risk while maximizing potential returns.";
            
            $story = $mockService->generateCatStory($mockDocument);
            
            $this->info("✅ Mock story generated successfully!");
            $this->info("   Story length: " . strlen($story) . " characters");
            $this->line("");
            $this->info("📝 Generated mock story:");
            $this->line("───────────────────────────────────────");
            $this->line($story);
            $this->line("───────────────────────────────────────");
            
        } catch (\Exception $e) {
            $this->error("❌ Mock story generation failed: " . $e->getMessage());
            return 1;
        }

        // Test 5: Test service resolution through container
        $this->info("5️⃣ Testing service resolution through Laravel container...");
        
        try {
            $resolvedService = app(App\Services\OpenAIService::class);
            $className = get_class($resolvedService);
            
            if (str_contains($className, 'Mock')) {
                $this->info("✅ Container resolved to mock service: {$className}");
            } else {
                $this->warn("⚠️  Container resolved to real service: {$className}");
                $this->info("   This might be why the simple test is failing");
            }
        } catch (\Exception $e) {
            $this->error("❌ Service resolution failed: " . $e->getMessage());
            $this->info("   Error: " . $e->getMessage());
        }

        $this->info("🎉 Direct mock test completed!");
        $this->line("");
        $this->info("📋 Summary:");
        $this->info("   ✅ Mock service works when created directly");
        $this->info("   📝 Mock story generation is functional");
        $this->info("   🔧 If other tests fail, it's a service resolution issue");
        
        return 0;
    }
}