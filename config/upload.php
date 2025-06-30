<?php

return [
    /*
    |--------------------------------------------------------------------------
    | File Upload Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for handling large file uploads
    | in the Cat Story Generator application.
    |
    */

    // Maximum file size for uploads (set to 2GB for unlimited support)
    'max_file_size' => env('UPLOAD_MAX_FILE_SIZE', '2048M'),

    // Maximum POST data size (should be same or larger than max_file_size)
    'max_post_size' => env('UPLOAD_MAX_POST_SIZE', '2048M'),

    // Maximum execution time for upload processing (in seconds)
    'max_execution_time' => env('UPLOAD_MAX_EXECUTION_TIME', 3600), // 1 hour

    // Maximum input time for receiving upload data (in seconds)
    'max_input_time' => env('UPLOAD_MAX_INPUT_TIME', 3600), // 1 hour

    // Memory limit for processing uploads
    'memory_limit' => env('UPLOAD_MEMORY_LIMIT', '2048M'),

    // Supported file types and their MIME types
    'supported_types' => [
        'pdf' => [
            'extensions' => ['pdf'],
            'mime_types' => ['application/pdf'],
            'description' => 'Portable Document Format'
        ],
        'word' => [
            'extensions' => ['doc', 'docx'],
            'mime_types' => [
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ],
            'description' => 'Microsoft Word Documents'
        ],
        'powerpoint' => [
            'extensions' => ['ppt', 'pptx'],
            'mime_types' => [
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation'
            ],
            'description' => 'Microsoft PowerPoint Presentations'
        ]
    ],

    // Storage configuration
    'storage' => [
        'disk' => env('UPLOAD_STORAGE_DISK', 'public'),
        'path' => 'documents/original',
        'temporary_path' => 'documents/temp',
    ],

    // Processing configuration
    'processing' => [
        // Queue name for document processing jobs
        'queue_name' => 'document-processing',
        
        // Queue name for AI story generation jobs
        'ai_queue_name' => 'cat-story-generation',
        
        // Maximum retries for failed processing
        'max_retries' => 3,
        
        // Timeout for processing jobs (in seconds)
        'job_timeout' => 3600, // 1 hour
        
        // Chunk size for processing very large documents (in characters)
        'chunk_size' => 50000, // 50KB chunks
        
        // Maximum content length before chunking is used
        'chunk_threshold' => 100000, // 100KB
    ],

    // AI Configuration
    'ai' => [
        // Maximum tokens for AI processing
        'max_tokens' => 2000,
        
        // AI model to use
        'model' => env('OPENAI_MODEL', 'gpt-3.5-turbo'),
        
        // Temperature for AI generation
        'temperature' => 0.8,
        
        // Maximum content length for single AI request
        'max_content_length' => 20000, // 20KB
    ],

    // Validation rules
    'validation' => [
        // Minimum content length after extraction
        'min_content_length' => 10,
        
        // Maximum filename length
        'max_filename_length' => 255,
        
        // Allowed characters in filenames (regex pattern)
        'filename_pattern' => '/^[a-zA-Z0-9\-_\. ]+$/',
    ],

    // Cleanup configuration
    'cleanup' => [
        // Delete failed uploads after X days
        'delete_failed_after_days' => 7,
        
        // Delete temporary files after X hours
        'delete_temp_after_hours' => 24,
        
        // Maximum storage per user (in bytes, 0 = unlimited)
        'max_user_storage' => 0,
    ]
];