<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Services\DocumentParserService;
use Illuminate\Console\Command;

class ProcessDocumentCommand extends Command
{
    protected $signature = 'document:process {id : Document ID}';
    protected $description = 'Process a specific document for testing';

    public function handle(DocumentParserService $parserService): int
    {
        $documentId = $this->argument('id');
        $document = Document::find($documentId);

        if (!$document) {
            $this->error("Document with ID {$documentId} not found.");
            return 1;
        }

        $this->info("Processing document: {$document->title}");
        $this->info("File type: {$document->file_type}");
        $this->info("File size: {$document->file_size_formatted}");

        $success = $parserService->parseDocument($document);

        if ($success) {
            $this->info("✅ Document processed successfully!");
            
            $stats = $parserService->getProcessingStats($document);
            $this->info("Word count: " . number_format($stats['word_count']));
            $this->info("Character count: " . number_format($stats['content_length']));
            $this->info("Estimated reading time: {$stats['estimated_reading_time']} minutes");
            
        } else {
            $this->error("❌ Document processing failed!");
            $this->error("Error: {$document->error_message}");
        }

        return $success ? 0 : 1;
    }
}