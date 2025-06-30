<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\DocumentParserService;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DocumentParserService::class, function ($app) {
            return new DocumentParserService();
        });
    }

    public function boot(): void
    {
        //
    }
}