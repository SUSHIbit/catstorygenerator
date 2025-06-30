<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ $document->title }}
            </h2>
            <div class="flex space-x-3">
                @if($document->isCompleted())
                    <a 
                        href="{{ route('documents.download', $document) }}" 
                        class="bg-slate-600 text-white px-4 py-2 rounded-lg hover:bg-slate-500 transition-colors"
                    >
                        Download Original
                    </a>
                @endif
                <a 
                    href="{{ route('documents.index') }}" 
                    class="bg-slate-800 text-white px-4 py-2 rounded-lg hover:bg-slate-700 transition-colors"
                >
                    Back to Documents
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Document Info -->
            <div class="bg-white rounded-lg shadow mb-6 p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <h4 class="font-medium text-slate-700 mb-2">File Information</h4>
                        <div class="space-y-1 text-sm text-slate-600">
                            <p><strong>Filename:</strong> {{ $document->filename }}</p>
                            <p><strong>Type:</strong> {{ strtoupper($document->file_type) }}</p>
                            <p><strong>Size:</strong> {{ $document->file_size_formatted }}</p>
                            <p><strong>Uploaded:</strong> {{ $document->created_at->format('M j, Y g:i A') }}</p>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="font-medium text-slate-700 mb-2">Processing Status</h4>
                        <div class="space-y-2">
                            @if($document->status === 'completed')
                                <div class="flex items-center text-green-600">
                                    <span class="text-xl mr-2">✅</span>
                                    <span class="font-medium">Completed</span>
                                </div>
                                @if($document->processed_at)
                                    <p class="text-sm text-slate-600">
                                        Processed: {{ $document->processed_at->format('M j, Y g:i A') }}
                                    </p>
                                @endif
                            @elseif($document->status === 'processing')
                                <div class="flex items-center text-yellow-600">
                                    <span class="text-xl mr-2">⏳</span>
                                    <span class="font-medium">Processing...</span>
                                </div>
                                <p class="text-sm text-slate-600">Your cat story is being generated!</p>
                            @elseif($document->status === 'failed')
                                <div class="flex items-center text-red-600">
                                    <span class="text-xl mr-2">❌</span>
                                    <span class="font-medium">Failed</span>
                                </div>
                                @if($document->error_message)
                                    <p class="text-sm text-red-600">{{ $document->error_message }}</p>
                                @endif
                            @else
                                <div class="flex items-center text-slate-600">
                                    <span class="text-xl mr-2">📤</span>
                                    <span class="font-medium">Uploaded</span>
                                </div>
                                <p class="text-sm text-slate-600">Ready for processing</p>
                            @endif
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="font-medium text-slate-700 mb-2">Cat Story Status</h4>
                        @if($document->hasStory())
                            <div class="flex items-center text-green-600">
                                <span class="text-xl mr-2">🐱</span>
                                <span class="font-medium">Story Ready!</span>
                            </div>
                        @else
                            <div class="flex items-center text-slate-400">
                                <span class="text-xl mr-2">🐱</span>
                                <span class="font-medium">No story yet</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Cat Story Display -->
            @if($document->hasStory())
                <div class="bg-white rounded-lg shadow mb-6 p-6">
                    <div class="flex items-center mb-4">
                        <span class="text-3xl mr-3">🐱</span>
                        <h3 class="text-xl font-bold text-slate-800">Cat Story</h3>
                    </div>
                    
                    <div class="bg-slate-50 rounded-lg p-6">
                        <div class="prose max-w-none text-slate-700 leading-relaxed">
                            {!! nl2br(e($document->cat_story)) !!}
                        </div>
                    </div>
                    
                    <div class="mt-4 flex justify-end">
                        <button 
                            onclick="copyStory()" 
                            class="bg-slate-600 text-white px-4 py-2 rounded-lg hover:bg-slate-500 transition-colors"
                        >
                            Copy Story
                        </button>
                    </div>
                </div>
            @endif

            <!-- Processing Status for Non-Completed Documents -->
            @if(!$document->isCompleted() && !$document->isFailed())
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6">
                    <div class="flex items-center mb-4">
                        <span class="text-2xl mr-3">⏳</span>
                        <h3 class="text-lg font-medium text-yellow-800">Processing in Progress</h3>
                    </div>
                    <p class="text-yellow-700 mb-4">
                        Your document is being processed by our AI cat narrator. This usually takes a few minutes.
                    </p>
                    <div class="bg-yellow-100 rounded-lg p-4">
                        <h4 class="font-medium text-yellow-800 mb-2">What's happening:</h4>
                        <ul class="text-sm text-yellow-700 space-y-1">
                            <li>• Extracting text from your document</li>
                            <li>• Analyzing content structure</li>
                            <li>• Generating simple cat story</li>
                            <li>• Quality checking the narrative</li>
                        </ul>
                    </div>
                    <div class="mt-4">
                        <button 
                            onclick="window.location.reload()" 
                            class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-500 transition-colors"
                        >
                            Refresh Status
                        </button>
                    </div>
                </div>
            @endif

            <!-- Error Display for Failed Documents -->
            @if($document->isFailed())
                <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
                    <div class="flex items-center mb-4">
                        <span class="text-2xl mr-3">❌</span>
                        <h3 class="text-lg font-medium text-red-800">Processing Failed</h3>
                    </div>
                    <p class="text-red-700 mb-4">
                        Unfortunately, we couldn't process your document. This might be due to:
                    </p>
                    <ul class="text-sm text-red-700 space-y-1 mb-4">
                        <li>• The document might be corrupted or password-protected</li>
                        <li>• The file format might not be fully supported</li>
                        <li>• The document might be too complex to process</li>
                        <li>• There might have been a temporary server issue</li>
                    </ul>
                    @if($document->error_message)
                        <div class="bg-red-100 rounded-lg p-3 mb-4">
                            <p class="text-sm text-red-800"><strong>Error details:</strong> {{ $document->error_message }}</p>
                        </div>
                    @endif
                    <div class="flex space-x-3">
                        <a 
                            href="{{ route('documents.create') }}" 
                            class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-500 transition-colors"
                        >
                            Try Another Document
                        </a>
                        <button 
                            onclick="retryProcessing()" 
                            class="bg-slate-600 text-white px-4 py-2 rounded-lg hover:bg-slate-500 transition-colors"
                        >
                            Retry Processing
                        </button>
                    </div>
                </div>
            @endif

            <!-- Actions -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-slate-800 mb-4">Actions</h3>
                <div class="flex flex-wrap gap-3">
                    @if($document->isCompleted())
                        <a 
                            href="{{ route('documents.download', $document) }}" 
                            class="bg-slate-600 text-white px-4 py-2 rounded-lg hover:bg-slate-500 transition-colors"
                        >
                            📁 Download Original
                        </a>
                    @endif
                    
                    <a 
                        href="{{ route('documents.create') }}" 
                        class="bg-slate-800 text-white px-4 py-2 rounded-lg hover:bg-slate-700 transition-colors"
                    >
                        📤 Upload Another Document
                    </a>
                    
                    <form action="{{ route('documents.destroy', $document) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button 
                            type="submit" 
                            class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-500 transition-colors"
                            onclick="return confirm('Are you sure you want to delete this document and its cat story?')"
                        >
                            🗑️ Delete Document
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function copyStory() {
            const story = @json($document->cat_story ?? '');
            if (story) {
                navigator.clipboard.writeText(story).then(function() {
                    // Create temporary notification
                    const notification = document.createElement('div');
                    notification.className = 'fixed top-4 right-4 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg z-50';
                    notification.textContent = 'Cat story copied to clipboard!';
                    document.body.appendChild(notification);
                    
                    setTimeout(() => {
                        notification.remove();
                    }, 3000);
                });
            }
        }

        function retryProcessing() {
            if (confirm('This will attempt to reprocess the document. Continue?')) {
                // In Phase 5, we'll implement the actual retry logic
                alert('Retry functionality will be implemented in Phase 5!');
            }
        }

        // Auto-refresh for processing documents
        @if($document->isProcessing())
            setTimeout(() => {
                window.location.reload();
            }, 30000); // Refresh every 30 seconds
        @endif
    </script>
    @endpush
</x-app-layout>