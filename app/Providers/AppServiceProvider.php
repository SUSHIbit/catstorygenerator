<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\DocumentParserService;
use App\Services\OpenAIService;

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

        // Register OpenAIService as singleton
        $this->app->singleton(OpenAIService::class, function ($app) {
            return new OpenAIService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // REMOVED: All the problematic configuration code
        // PHP limits should be set in php.ini or .htaccess instead
    }
}