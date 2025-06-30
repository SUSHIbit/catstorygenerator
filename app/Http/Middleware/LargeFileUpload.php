<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LargeFileUpload
{
    /**
     * Handle an incoming request and set appropriate limits for large file uploads.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if this is a document-related route that might involve file uploads
        if ($this->isDocumentRoute($request)) {
            $this->setLargeFileLimits();
        }

        return $next($request);
    }

    /**
     * Determine if the current route is document-related
     */
    private function isDocumentRoute(Request $request): bool
    {
        $path = $request->path();
        
        return str_starts_with($path, 'documents') || 
               $request->routeIs('documents.*') ||
               $request->is('documents/*') ||
               $request->is('documents');
    }

    /**
     * Set PHP configuration for handling large file uploads
     */
    private function setLargeFileLimits(): void
    {
        // Get limits from config or use defaults
        $uploadMaxFilesize = config('upload.max_file_size', '2048M');
        $postMaxSize = config('upload.max_post_size', '2048M');
        $maxExecutionTime = config('upload.max_execution_time', 3600);
        $maxInputTime = config('upload.max_input_time', 3600);
        $memoryLimit = config('upload.memory_limit', '2048M');

        // Set PHP ini values for large file processing
        ini_set('upload_max_filesize', $uploadMaxFilesize);
        ini_set('post_max_size', $postMaxSize);
        ini_set('max_execution_time', $maxExecutionTime);
        ini_set('max_input_time', $maxInputTime);
        ini_set('memory_limit', $memoryLimit);

        // Additional settings for better file upload handling
        ini_set('max_file_uploads', '20');
        ini_set('file_uploads', '1');
        
        // Increase buffer sizes for large files
        ini_set('output_buffering', 'Off');
        
        // Log the limits being set (for debugging)
        \Log::info('Large file limits set', [
            'upload_max_filesize' => $uploadMaxFilesize,
            'post_max_size' => $postMaxSize,
            'max_execution_time' => $maxExecutionTime,
            'memory_limit' => $memoryLimit,
            'route' => request()->path(),
        ]);
    }
}