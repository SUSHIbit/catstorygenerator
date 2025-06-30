<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\DocumentParserService;
use App\Services\OpenAIService;
use OpenAI\Client;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DocumentParserService::class, function ($app) {
            return new DocumentParserService();
        });

        $this->app->singleton(OpenAIService::class, function ($app) {
            $client = $app->make(Client::class);
            return new OpenAIService($client);
        });
    }

    public function boot(): void
    {
        //
    }
}