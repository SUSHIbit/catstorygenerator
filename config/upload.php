<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Upload Configuration
    |--------------------------------------------------------------------------
    |
    | This file is for configuring file upload limits and processing settings
    | for the Cat Story Generator application. These settings allow for
    | unlimited document processing of any size.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Maximum File Upload Size
    |--------------------------------------------------------------------------
    |
    | This value determines the maximum file size that can be uploaded.
    | Set to 2048M (2GB) to allow very large documents.
    |
    */
    'max_file_size' => env('UPLOAD_MAX_FILESIZE', '2048M'),

    /*
    |--------------------------------------------------------------------------
    | Maximum POST Size
    |--------------------------------------------------------------------------
    |
    | This value determines the maximum size of POST data that will be accepted.
    | This should be larger than max_file_size to account for other form data.
    |
    */
    'max_post_size' => env('POST_MAX_SIZE', '2048M'),

    /*
    |--------------------------------------------------------------------------
    | Maximum Execution Time
    |--------------------------------------------------------------------------
    |
    | This value determines how long (in seconds) a script is allowed to run
    | before it is terminated. Set to 1 hour (3600 seconds) for large files.
    |
    */
    'max_execution_time' => env('MAX_EXECUTION_TIME', 3600),

    /*
    |--------------------------------------------------------------------------
    | Maximum Input Time
    |--------------------------------------------------------------------------
    |
    | This value determines how long (in seconds) a script is allowed to parse
    | input data. Set to 1 hour (3600 seconds) for large uploads.
    |
    */
    'max_input_time' => env('MAX_INPUT_TIME', 3600),

    /*
    |--------------------------------------------------------------------------
    | Memory Limit
    |--------------------------------------------------------------------------
    |
    | This value determines the maximum amount of memory a script may consume.
    | Set to 2048M (2GB) for processing large documents.
    |
    */
    'memory_limit' => env('MEMORY_LIMIT', '2048M'),

    /*
    |--------------------------------------------------------------------------
    | Allowed File Types
    |--------------------------------------------------------------------------
    |
    | This array contains the file extensions that are allowed for upload.
    | Only document types that can be processed into cat stories.
    |
    */
    'allowed_extensions' => [
        'pdf',
        'doc',
        'docx',
        'ppt',
        'pptx',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed MIME Types
    |--------------------------------------------------------------------------
    |
    | This array contains the MIME types that correspond to the allowed
    | file extensions for additional validation.
    |
    */
    'allowed_mime_types' => [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ],

    /*
    |--------------------------------------------------------------------------
    | Processing Settings
    |--------------------------------------------------------------------------
    |
    | These settings control how documents are processed and converted
    | into cat stories.
    |
    */

    // Maximum content length before chunking (in characters)
    'max_content_length' => env('MAX_CONTENT_LENGTH', 100000),

    // Chunk size for processing large content (in characters)
    'chunk_size' => env('CHUNK_SIZE', 15000),

    // Maximum number of chunks to process
    'max_chunks' => env('MAX_CHUNKS', 5),

    // Progress logging interval (process every X pages/slides/sections)
    'progress_log_interval' => env('PROGRESS_LOG_INTERVAL', 100),

    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    |
    | These settings control how document processing jobs are queued
    | and executed for optimal performance.
    |
    */

    // Job timeout for document processing (in seconds)
    'processing_timeout' => env('PROCESSING_TIMEOUT', 3600),

    // Job timeout for cat story generation (in seconds)
    'story_generation_timeout' => env('STORY_GENERATION_TIMEOUT', 1800),

    // Number of retry attempts for failed jobs
    'max_job_attempts' => env('MAX_JOB_ATTEMPTS', 3),

    // Backoff delays between retry attempts (in seconds)
    'job_backoff_delays' => [60, 300, 900], // 1 min, 5 min, 15 min

];