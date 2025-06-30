<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DiagnoseOpenAICommand extends Command
{
    protected $signature = 'diagnose:openai';
    protected $description = 'Diagnose OpenAI API connection issues';

    public function handle(): int
    {
        $this->info("🔍 Diagnosing OpenAI API connection...");
        
        // Check 1: API Key format
        $apiKey = config('services.openai.api_key') ?? env('OPENAI_API_KEY');
        
        if (empty($apiKey)) {
            $this->error("❌ No API key found");
            $this->info("   Please add OPENAI_API_KEY=your_key_here to your .env file");
            return 1;
        }
        
        $this->info("✅ API key is configured");
        $this->info("   Key starts with: " . substr($apiKey, 0, 8) . "...");
        
        if (!str_starts_with($apiKey, 'sk-')) {
            $this->warn("⚠️  API key doesn't start with 'sk-' - this might be incorrect");
        }
        
        // Check 2: Basic connectivity test
        $this->info("🌐 Testing basic internet connectivity to OpenAI...");
        
        try {
            $response = Http::timeout(10)->get('https://api.openai.com/v1/models');
            $this->info("✅ Can reach OpenAI servers (HTTP {$response->status()})");
            
            if ($response->status() === 401) {
                $this->info("   (401 Unauthorized is expected without proper auth)");
            }
        } catch (\Exception $e) {
            $this->error("❌ Cannot reach OpenAI servers: " . $e->getMessage());
            $this->info("   This might be a firewall or network issue");
            return 1;
        }
        
        // Check 3: API Key authentication test
        $this->info("🔑 Testing API key authentication...");
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout(15)
            ->get('https://api.openai.com/v1/models');
            
            if ($response->successful()) {
                $this->info("✅ API key authentication successful");
                $data = $response->json();
                if (isset($data['data'])) {
                    $modelCount = count($data['data']);
                    $this->info("   Available models: {$modelCount}");
                }
            } else {
                $this->error("❌ API key authentication failed");
                $this->error("   HTTP Status: " . $response->status());
                $this->error("   Response: " . $response->body());
                
                if ($response->status() === 401) {
                    $this->info("   ➜ Your API key is invalid or expired");
                    $this->info("   ➜ Check your API key at https://platform.openai.com/api-keys");
                } elseif ($response->status() === 429) {
                    $this->info("   ➜ Rate limit exceeded or quota reached");
                    $this->info("   ➜ Check your usage at https://platform.openai.com/usage");
                }
                
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("❌ API authentication test failed: " . $e->getMessage());
            return 1;
        }
        
        // Check 4: Simple completion test
        $this->info("🤖 Testing simple completion...");
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout(30)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Say "Hello" in one word.'
                    ]
                ],
                'max_tokens' => 5
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['choices'][0]['message']['content'])) {
                    $response_text = trim($data['choices'][0]['message']['content']);
                    $this->info("✅ Simple completion test successful");
                    $this->info("   Response: '{$response_text}'");
                } else {
                    $this->warn("⚠️  Completion successful but unexpected response format");
                    $this->info("   Response: " . json_encode($data));
                }
            } else {
                $this->error("❌ Simple completion test failed");
                $this->error("   HTTP Status: " . $response->status());
                $this->error("   Response: " . $response->body());
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("❌ Simple completion test failed: " . $e->getMessage());
            return 1;
        }
        
        // All tests passed
        $this->info("🎉 All OpenAI diagnostics passed!");
        $this->info("📋 Your OpenAI integration should work correctly now.");
        
        return 0;
    }
}