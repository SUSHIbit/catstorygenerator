<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Jobs\ProcessDocumentJob;
use App\Jobs\GenerateCatStoryJob;
use App\Services\DocumentParserService;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class DocumentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Document::class, 'document');
    }

    public function index(): View
    {
        $documents = auth()->user()
            ->documents()
            ->recent()
            ->paginate(10);

        $stats = [
            'total' => auth()->user()->documents()->count(),
            'completed' => auth()->user()->documents()->completed()->count(),
            'processing' => auth()->user()->documents()->where('status', 'processing')->count(),
            'failed' => auth()->user()->documents()->where('status', 'failed')->count(),
        ];

        return view('documents.index', compact('documents', 'stats'));
    }

    public function show(Document $document, DocumentParserService $parserService): View
    {
        $stats = $parserService->getProcessingStats($document);
        return view('documents.show', compact('document', 'stats'));
    }

    public function create(): View
    {
        // Get PHP upload limits for display
        $uploadLimits = $this->getUploadLimits();
        return view('documents.create', compact('uploadLimits'));
    }

    public function store(Request $request): RedirectResponse
    {
        // Enhanced validation with better error handling
        try {
            // First, check if we have a file at all
            if (!$request->hasFile('file')) {
                Log::error('Upload failed: No file in request', [
                    'post_data' => $request->all(),
                    'files_data' => $_FILES ?? 'No $_FILES data'
                ]);
                
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['file' => 'No file was uploaded. Please select a file and try again.']);
            }

            $file = $request->file('file');
            
            // Check for upload errors
            if (!$file->isValid()) {
                $error = $file->getError();
                $errorMessage = $this->getUploadErrorMessage($error);
                
                Log::error('Upload failed: File upload error', [
                    'error_code' => $error,
                    'error_message' => $errorMessage,
                    'file_size' => $file->getSize(),
                    'php_limits' => $this->getUploadLimits()
                ]);

                return redirect()->back()
                    ->withInput()
                    ->withErrors(['file' => $errorMessage]);
            }

            // Validate file
            $request->validate([
                'file' => [
                    'required',
                    'file',
                    'max:' . $this->getMaxFileSizeInKB(), // Dynamic max size based on PHP settings
                    'mimes:pdf,doc,docx,ppt,pptx'
                ],
                'title' => 'nullable|string|max:255'
            ], [
                'file.max' => 'The file is too large. Maximum allowed size is ' . $this->getMaxFileSizeFormatted() . '.',
                'file.mimes' => 'Only PDF, DOC, DOCX, PPT, and PPTX files are allowed.',
                'file.required' => 'Please select a file to upload.',
            ]);

            Log::info('Upload validation passed', [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'file_type' => $file->getClientOriginalExtension(),
            ]);

            // Generate unique filename
            $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            
            // Store file with better error handling
            try {
                $filepath = $file->storeAs('documents/original', $filename, 'public');
                
                if (!$filepath) {
                    throw new \Exception('Failed to store file');
                }
                
                Log::info('File stored successfully', ['filepath' => $filepath]);
                
            } catch (\Exception $e) {
                Log::error('File storage failed', [
                    'error' => $e->getMessage(),
                    'file_name' => $file->getClientOriginalName(),
                    'storage_path' => storage_path('app/public/documents/original')
                ]);
                
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['file' => 'Failed to save the uploaded file. Please try again.']);
            }
            
            // Create document record
            $document = Document::create([
                'user_id' => auth()->id(),
                'title' => $request->title ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'filename' => $file->getClientOriginalName(),
                'filepath' => $filepath,
                'file_type' => strtolower($file->getClientOriginalExtension()),
                'file_size' => $file->getSize(),
                'status' => 'uploaded'
            ]);

            Log::info('Document record created', ['document_id' => $document->id]);

            // Dispatch processing job
            ProcessDocumentJob::dispatch($document);

            return redirect()->route('documents.show', $document)
                ->with('success', 'Document uploaded successfully! Processing has started and your cat story will be generated soon.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Laravel validation errors
            Log::error('Upload validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->except(['file'])
            ]);
            
            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors());
                
        } catch (\Exception $e) {
            // Any other unexpected errors
            Log::error('Unexpected upload error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['file'])
            ]);
            
            return redirect()->back()
                ->withInput()
                ->withErrors(['file' => 'An unexpected error occurred during upload. Please try again.']);
        }
    }

    /**
     * Get upload limits from PHP configuration
     */
    private function getUploadLimits(): array
    {
        $uploadMaxFilesize = ini_get('upload_max_filesize');
        $postMaxSize = ini_get('post_max_size');
        $memoryLimit = ini_get('memory_limit');
        $maxExecutionTime = ini_get('max_execution_time');
        $maxInputTime = ini_get('max_input_time');

        return [
            'upload_max_filesize' => $uploadMaxFilesize,
            'upload_max_filesize_bytes' => $this->parseSize($uploadMaxFilesize),
            'post_max_size' => $postMaxSize,
            'post_max_size_bytes' => $this->parseSize($postMaxSize),
            'memory_limit' => $memoryLimit,
            'max_execution_time' => $maxExecutionTime,
            'max_input_time' => $maxInputTime,
        ];
    }

    /**
     * Get maximum file size in KB for Laravel validation
     */
    private function getMaxFileSizeInKB(): int
    {
        $limits = $this->getUploadLimits();
        $maxSize = min($limits['upload_max_filesize_bytes'], $limits['post_max_size_bytes']);
        return floor($maxSize / 1024); // Convert to KB for Laravel validation
    }

    /**
     * Get formatted maximum file size for display
     */
    private function getMaxFileSizeFormatted(): string
    {
        $limits = $this->getUploadLimits();
        $maxSize = min($limits['upload_max_filesize_bytes'], $limits['post_max_size_bytes']);
        return $this->formatBytes($maxSize);
    }

    /**
     * Parse size string (like "2M") to bytes
     */
    private function parseSize(string $size): int
    {
        $size = trim($size);
        $last = strtolower($size[strlen($size) - 1]);
        $size = (int) $size;

        switch ($last) {
            case 'g': $size *= 1024;
            case 'm': $size *= 1024;
            case 'k': $size *= 1024;
        }

        return $size;
    }

    /**
     * Format bytes to human readable string
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }

    /**
     * Get user-friendly error message for upload errors
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the maximum file size allowed by the server (' . ini_get('upload_max_filesize') . ').';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the maximum file size specified in the form.';
            case UPLOAD_ERR_PARTIAL:
                return 'The file was only partially uploaded. Please try again.';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded. Please select a file and try again.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder. Please contact support.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk. Please contact support.';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload. Please contact support.';
            default:
                return 'An unknown error occurred during file upload. Please try again.';
        }
    }

    public function destroy(Document $document): RedirectResponse
    {
        $document->delete();
        
        return redirect()->route('documents.index')
            ->with('success', 'Document deleted successfully.');
    }

    public function download(Document $document)
    {
        if (!Storage::disk('public')->exists($document->filepath)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('public')->download($document->filepath, $document->filename);
    }

    /**
     * Retry processing a failed document
     */
    public function retry(Document $document): RedirectResponse
    {
        if (!$document->isFailed()) {
            return redirect()->route('documents.show', $document)
                ->with('error', 'Only failed documents can be retried.');
        }

        // Reset status and dispatch processing job
        $document->update([
            'status' => 'uploaded',
            'error_message' => null
        ]);

        ProcessDocumentJob::dispatch($document);

        return redirect()->route('documents.show', $document)
            ->with('success', 'Document processing has been restarted.');
    }

    /**
     * Process document immediately (for testing)
     */
    public function processNow(Document $document, DocumentParserService $parserService): RedirectResponse
    {
        // Check if document already has content but no story
        if ($document->original_content && !$document->hasStory()) {
            // Just generate the cat story
            $success = $parserService->generateCatStoryNow($document);
            
            if ($success) {
                return redirect()->route('documents.show', $document)
                    ->with('success', 'Cat story generation has been started! Check back in a few minutes.');
            } else {
                return redirect()->route('documents.show', $document)
                    ->with('error', 'Failed to start cat story generation. Please try again.');
            }
        }

        // Process the entire document if not processed yet
        if ($document->isCompleted() || $document->isProcessing()) {
            return redirect()->route('documents.show', $document)
                ->with('error', 'Document is already processed or being processed.');
        }

        $success = $parserService->parseDocument($document);

        if ($success) {
            return redirect()->route('documents.show', $document)
                ->with('success', 'Document processing started! Your cat story will be generated soon.');
        } else {
            return redirect()->route('documents.show', $document)
                ->with('error', 'Document processing failed. Check the error details.');
        }
    }

    /**
     * Generate cat story only (when document already has content)
     */
    public function generateStory(Document $document, OpenAIService $openAIService): RedirectResponse
    {
        if (!$document->original_content) {
            return redirect()->route('documents.show', $document)
                ->with('error', 'Document must be processed first before generating a cat story.');
        }

        if ($document->hasStory()) {
            return redirect()->route('documents.show', $document)
                ->with('info', 'Document already has a cat story.');
        }

        // Check if OpenAI service is available
        if (!$openAIService->isAvailable()) {
            return redirect()->route('documents.show', $document)
                ->with('error', 'AI service is currently unavailable. Please try again later.');
        }

        // Dispatch cat story generation job
        GenerateCatStoryJob::dispatch($document);

        return redirect()->route('documents.show', $document)
            ->with('success', 'Cat story generation has been started! This may take a few minutes.');
    }

    /**
     * Regenerate cat story (replace existing story)
     */
    public function regenerateStory(Document $document, OpenAIService $openAIService): RedirectResponse
    {
        if (!$document->original_content) {
            return redirect()->route('documents.show', $document)
                ->with('error', 'Document must be processed first before generating a cat story.');
        }

        // Check if OpenAI service is available
        if (!$openAIService->isAvailable()) {
            return redirect()->route('documents.show', $document)
                ->with('error', 'AI service is currently unavailable. Please try again later.');
        }

        // Clear existing story and reset status
        $document->update([
            'cat_story' => null,
            'status' => 'uploaded',
            'processed_at' => null
        ]);

        // Dispatch cat story generation job
        GenerateCatStoryJob::dispatch($document);

        return redirect()->route('documents.show', $document)
            ->with('success', 'Cat story regeneration has been started! This may take a few minutes.');
    }
}