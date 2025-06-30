<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\DocumentParserService;
use App\Services\OpenAIService;
use App\Services\MockOpenAIService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register DocumentParserService as singleton
        $this->app->singleton(DocumentParserService::class, function ($app) {
            return new DocumentParserService();
        });

        // Register OpenAIService as singleton - with mock option for testing
        $this->app->singleton(OpenAIService::class, function ($app) {
            // Use mock service if OPENAI_MOCK is set to true in .env
            if (env('OPENAI_MOCK', false)) {
                return new MockOpenAIService();
            }
            
            return new OpenAIService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}