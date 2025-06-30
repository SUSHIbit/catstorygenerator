<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use OpenAI\Client;

class OpenAIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Client::class, function ($app) {
            return \OpenAI::client(config('services.openai.api_key'));
        });
    }

    public function boot(): void
    {
        //
    }
}