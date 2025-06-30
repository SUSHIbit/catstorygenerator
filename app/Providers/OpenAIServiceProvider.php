<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use OpenAI\Client;
use OpenAI;
use GuzzleHttp\Client as GuzzleClient;

class OpenAIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Client::class, function ($app) {
            $apiKey = config('services.openai.api_key');
            
            if (empty($apiKey)) {
                throw new \Exception('OpenAI API key not configured. Please set OPENAI_API_KEY in your .env file.');
            }
            
            // Create Guzzle client with SSL verification disabled for local development
            $httpClient = new GuzzleClient([
                'verify' => false, // Disable SSL verification
                'timeout' => 60,
                'connect_timeout' => 30,
            ]);
            
            return OpenAI::factory()
                ->withApiKey($apiKey)
                ->withHttpClient($httpClient)
                ->make();
        });
    }

    public function boot(): void
    {
        //
    }
}