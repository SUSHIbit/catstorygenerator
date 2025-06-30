<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\DocumentParserService;
use App\Services\OpenAIService;
use OpenAI\Client;

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
            $client = $app->make(Client::class);
            return new OpenAIService($client);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set global PHP limits for large file processing
        $this->setGlobalLimits();
        
        // Set additional configuration for better performance
        $this->optimizeForLargeFiles();
    }

    /**
     * Set global PHP limits for large file processing
     */
    private function setGlobalLimits(): void
    {
        // Get limits from config with fallbacks
        $uploadMaxFilesize = config('upload.max_file_size', '2048M');
        $postMaxSize = config('upload.max_post_size', '2048M');
        $maxExecutionTime = config('upload.max_execution_time', 3600);
        $maxInputTime = config('upload.max_input_time', 3600);
        $memoryLimit = config('upload.memory_limit', '2048M');

        // Set PHP ini values globally
        ini_set('upload_max_filesize', $uploadMaxFilesize);
        ini_set('post_max_size', $postMaxSize);
        ini_set('max_execution_time', $maxExecutionTime);
        ini_set('max_input_time', $maxInputTime);
        ini_set('memory_limit', $memoryLimit);
        
        // Additional file upload settings
        ini_set('max_file_uploads', '20');
        ini_set('file_uploads', '1');
    }

    /**
     * Set additional optimizations for large file processing
     */
    private function optimizeForLargeFiles(): void
    {
        // Increase default timeout for HTTP requests
        ini_set('default_socket_timeout', '300'); // 5 minutes
        
        // Optimize garbage collection for memory management
        ini_set('zend.enable_gc', '1');
        
        // Set larger buffer for output
        ini_set('output_buffering', '0');
        
        // Optimize for large string operations
        ini_set('pcre.backtrack_limit', '5000000');
        ini_set('pcre.recursion_limit', '100000');
        
        // Log configuration (only in debug mode)
        if (config('app.debug')) {
            \Log::info('Large file configuration applied', [
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
            ]);
        }
    }
}