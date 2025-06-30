<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Jobs\ProcessDocumentJob;
use App\Services\DocumentParserService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        return view('documents.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB max
                'mimes:pdf,doc,docx,ppt,pptx'
            ],
            'title' => 'nullable|string|max:255'
        ]);

        $file = $request->file('file');
        
        // Generate unique filename
        $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        
        // Store file
        $filepath = $file->storeAs('documents/original', $filename, 'public');
        
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

        // Dispatch processing job
        ProcessDocumentJob::dispatch($document);

        return redirect()->route('documents.show', $document)
            ->with('success', 'Document uploaded successfully! Processing has started.');
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
        if ($document->isCompleted() || $document->isProcessing()) {
            return redirect()->route('documents.show', $document)
                ->with('error', 'Document is already processed or being processed.');
        }

        $success = $parserService->parseDocument($document);

        if ($success) {
            return redirect()->route('documents.show', $document)
                ->with('success', 'Document processed successfully!');
        } else {
            return redirect()->route('documents.show', $document)
                ->with('error', 'Document processing failed. Check the error details.');
        }
    }
}