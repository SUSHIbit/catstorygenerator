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

            <!-- Document Analysis Section -->
            @if($document->original_content)
                <div class="bg-white rounded-lg shadow mb-6 p-6">
                    <div class="flex items-center mb-4">
                        <span class="text-3xl mr-3">📊</span>
                        <h3 class="text-xl font-bold text-slate-800">Document Analysis</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div>
                            <h4 class="font-medium text-slate-700 mb-2">Content Stats</h4>
                            <div class="space-y-1 text-sm text-slate-600">
                                <p><strong>Word Count:</strong> {{ number_format($stats['word_count']) }}</p>
                                <p><strong>Characters:</strong> {{ number_format($stats['content_length']) }}</p>
                                <p><strong>Est. Reading Time:</strong> {{ $stats['estimated_reading_time'] }} min</p>
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="font-medium text-slate-700 mb-2">Processing Status</h4>
                            <div class="space-y-1 text-sm text-slate-600">
                                <p><strong>Text Extracted:</strong> 
                                    <span class="text-green-600">✅ Yes</span>
                                </p>
                                <p><strong>Ready for AI:</strong> 
                                    @if($document->hasStory())
                                        <span class="text-green-600">✅ Complete</span>
                                    @else
                                        <span class="text-yellow-600">⏳ Pending</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="font-medium text-slate-700 mb-2">Content Preview</h4>
                            <div class="text-sm text-slate-600">
                                <p class="line-clamp-3">
                                    {{ Str::limit($document->original_content, 150) }}
                                </p>
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="font-medium text-slate-700 mb-2">Actions</h4>
                            <div class="space-y-2">
                                <button 
                                    onclick="showOriginalContent()" 
                                    class="w-full bg-slate-600 text-white px-3 py-2 rounded text-sm hover:bg-slate-500 transition-colors"
                                >
                                    View Original Text
                                </button>
                                @if(!$document->hasStory())
                                    <form action="{{ route('documents.generate-story', $document) }}" method="POST">
                                        @csrf
                                        <button 
                                            type="submit" 
                                            class="w-full bg-yellow-600 text-white px-3 py-2 rounded text-sm hover:bg-yellow-500 transition-colors"
                                        >
                                            🐱 Generate Cat Story
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('documents.regenerate-story', $document) }}" method="POST">
                                        @csrf
                                        <button 
                                            type="submit" 
                                            class="w-full bg-purple-600 text-white px-3 py-2 rounded text-sm hover:bg-purple-500 transition-colors"
                                            onclick="return confirm('This will replace the current cat story. Continue?')"
                                        >
                                            🔄 Regenerate Story
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Cat Story Display -->
            @if($document->hasStory())
                <div class="bg-white rounded-lg shadow mb-6 p-6">
                    <div class="flex items-center mb-4">
                        <span class="text-3xl mr-3">🐱</span>
                        <h3 class="text-xl font-bold text-slate-800">Cat Story</h3>
                    </div>
                    
                    <div class="bg-slate-50 rounded-lg p-6">
                        <div class="prose max-w-none text-slate-700 leading-relaxed whitespace-pre-line">
                            {!! nl2br(e($document->cat_story)) !!}
                        </div>
                    </div>
                    
                    <div class="mt-4 flex justify-between items-center">
                        <div class="text-sm text-slate-500">
                            Generated: {{ $document->processed_at ? $document->processed_at->format('M j, Y g:i A') : 'Unknown' }}
                        </div>
                        <div class="flex space-x-2">
                            <button 
                                onclick="copyStory()" 
                                class="bg-slate-600 text-white px-4 py-2 rounded-lg hover:bg-slate-500 transition-colors"
                            >
                                📋 Copy Story
                            </button>
                            <form action="{{ route('documents.regenerate-story', $document) }}" method="POST" class="inline">
                                @csrf
                                <button 
                                    type="submit" 
                                    class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-500 transition-colors"
                                    onclick="return confirm('This will generate a new cat story, replacing the current one. Continue?')"
                                >
                                    🔄 Regenerate
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Processing Status for Non-Completed Documents -->
            @if($document->original_content && !$document->hasStory() && !$document->isFailed())
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                    <div class="flex items-center mb-4">
                        <span class="text-2xl mr-3">🐱</span>
                        <h3 class="text-lg font-medium text-blue-800">Ready for Cat Story Generation</h3>
                    </div>
                    <p class="text-blue-700 mb-4">
                        Your document has been processed and is ready to be transformed into a cat story!
                    </p>
                    <div class="bg-blue-100 rounded-lg p-4 mb-4">
                        <h4 class="font-medium text-blue-800 mb-2">What happens next:</h4>
                        <ul class="text-sm text-blue-700 space-y-1">
                            <li>• Our AI cat narrator will read your document</li>
                            <li>• Complex ideas will be simplified into cat language</li>
                            <li>• You'll get an entertaining, educational story</li>
                            <li>• This usually takes 1-3 minutes</li>
                        </ul>
                    </div>
                    <div class="flex space-x-3">
                        <form action="{{ route('documents.generate-story', $document) }}" method="POST">
                            @csrf
                            <button 
                                type="submit" 
                                class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-500 transition-colors"
                            >
                                🐱 Generate Cat Story Now
                            </button>
                        </form>
                        <button 
                            onclick="showOriginalContent()" 
                            class="bg-slate-600 text-white px-6 py-3 rounded-lg hover:bg-slate-500 transition-colors"
                        >
                            📖 Preview Content
                        </button>
                    </div>
                </div>
            @elseif(!$document->original_content && !$document->isFailed())
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6">
                    <div class="flex items-center mb-4">
                        <span class="text-2xl mr-3">⏳</span>
                        <h3 class="text-lg font-medium text-yellow-800">Document Processing in Progress</h3>
                    </div>
                    <p class="text-yellow-700 mb-4">
                        We're extracting text from your document. Once complete, we'll automatically generate your cat story.
                    </p>
                    <div class="bg-yellow-100 rounded-lg p-4">
                        <h4 class="font-medium text-yellow-800 mb-2">Current progress:</h4>
                        <ul class="text-sm text-yellow-700 space-y-1">
                            <li>• Extracting text from your document</li>
                            <li>• Analyzing content structure</li>
                            <li>• Preparing for cat story generation</li>
                        </ul>
                    </div>
                    <div class="mt-4">
                        <button 
                            onclick="window.location.reload()" 
                            class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-500 transition-colors"
                        >
                            🔄 Check Progress
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

    <!-- Original Content Modal -->
    <div id="originalContentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-4xl w-full max-h-96 overflow-hidden">
                <div class="flex justify-between items-center p-6 border-b">
                    <h3 class="text-lg font-bold text-slate-800">Original Document Content</h3>
                    <button onclick="hideOriginalContent()" class="text-slate-500 hover:text-slate-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="p-6 overflow-y-auto max-h-80">
                    <div class="prose max-w-none text-slate-700 text-sm leading-relaxed whitespace-pre-wrap">
                        {{ $document->original_content ?? 'No content available' }}
                    </div>
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
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("documents.retry", $document) }}';
                
                const token = document.createElement('input');
                token.type = 'hidden';
                token.name = '_token';
                token.value = '{{ csrf_token() }}';
                form.appendChild(token);
                
                document.body.appendChild(form);
                form.submit();
            }
        }

        function showOriginalContent() {
            document.getElementById('originalContentModal').classList.remove('hidden');
        }

        function hideOriginalContent() {
            document.getElementById('originalContentModal').classList.add('hidden');
        }

        // Close modal on outside click
        document.getElementById('originalContentModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideOriginalContent();
            }
        });

        // Auto-refresh for processing documents
        @if($document->isProcessing())
            setTimeout(() => {
                window.location.reload();
            }, 30000); // Refresh every 30 seconds
        @endif
    </script>
    @endpush
</x-app-layout>