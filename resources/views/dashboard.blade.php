<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center">
            <div>
                <h2 class="font-bold text-2xl text-slate-800 leading-tight flex items-center">
                    <span class="text-3xl mr-3">🏠</span>
                    {{ __('Dashboard') }}
                </h2>
                <p class="text-slate-600 mt-1">Transform complex documents into simple cat stories</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <a href="{{ route('documents.create') }}" 
                   class="inline-flex items-center gradient-bg text-white px-6 py-3 rounded-xl hover:opacity-90 transition-opacity font-medium shadow-lg">
                    <span class="mr-2">📤</span>
                    Upload Document
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            @php
                $userStats = [
                    'total' => auth()->user()->documents()->count(),
                    'completed' => auth()->user()->documents()->completed()->count(),
                    'processing' => auth()->user()->documents()->where('status', 'processing')->count(),
                    'failed' => auth()->user()->documents()->where('status', 'failed')->count(),
                    'recent' => auth()->user()->getRecentDocuments(3)
                ];
            @endphp

            @if($userStats['total'] == 0)
                <!-- Welcome Section for New Users -->
                <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-slate-200">
                    <div class="p-8 text-center">
                        <div class="text-8xl mb-6 float-animation">🐱</div>
                        <h3 class="text-3xl font-bold text-slate-800 mb-4">Welcome to Cat Story Generator!</h3>
                        <p class="text-lg text-slate-600 mb-8 max-w-2xl mx-auto">
                            Transform complex documents into simple, entertaining cat stories. Perfect for students who want to understand difficult content through fun, memorable narratives.
                        </p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 max-w-4xl mx-auto">
                            <div class="bg-slate-50 rounded-xl p-6 card-hover">
                                <div class="text-4xl mb-3">📄</div>
                                <h4 class="font-semibold text-slate-800 mb-2">Upload Documents</h4>
                                <p class="text-sm text-slate-600">Support for PDF, DOC, DOCX, PPT, and PPTX files up to 10MB</p>
                            </div>
                            <div class="bg-slate-50 rounded-xl p-6 card-hover">
                                <div class="text-4xl mb-3">🤖</div>
                                <h4 class="font-semibold text-slate-800 mb-2">AI Processing</h4>
                                <p class="text-sm text-slate-600">Our AI cat narrator transforms complex content into simple stories</p>
                            </div>
                            <div class="bg-slate-50 rounded-xl p-6 card-hover">
                                <div class="text-4xl mb-3">📚</div>
                                <h4 class="font-semibold text-slate-800 mb-2">Easy Learning</h4>
                                <p class="text-sm text-slate-600">Understand difficult concepts through entertaining cat stories</p>
                            </div>
                        </div>
                        
                        <a href="{{ route('documents.create') }}" 
                           class="inline-flex items-center gradient-bg text-white px-8 py-4 rounded-xl hover:opacity-90 transition-opacity font-medium text-lg shadow-lg">
                            <span class="mr-3">🚀</span>
                            Upload Your First Document
                        </a>
                    </div>
                </div>
            @else
                <!-- Stats Overview -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-white rounded-xl shadow-sm p-6 card-hover border border-slate-200">
                        <div class="flex items-center">
                            <div class="text-4xl mr-4">📚</div>
                            <div>
                                <p class="text-slate-600 text-sm font-medium">Total Documents</p>
                                <p class="text-3xl font-bold text-slate-800">{{ $userStats['total'] }}</p>
                                <p class="text-xs text-slate-500 mt-1">All time</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm p-6 card-hover border border-slate-200">
                        <div class="flex items-center">
                            <div class="text-4xl mr-4">🐱</div>
                            <div>
                                <p class="text-slate-600 text-sm font-medium">Cat Stories</p>
                                <p class="text-3xl font-bold text-green-600">{{ $userStats['completed'] }}</p>
                                <p class="text-xs text-slate-500 mt-1">Ready to read</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm p-6 card-hover border border-slate-200">
                        <div class="flex items-center">
                            <div class="text-4xl mr-4">⏳</div>
                            <div>
                                <p class="text-slate-600 text-sm font-medium">Processing</p>
                                <p class="text-3xl font-bold text-yellow-600">{{ $userStats['processing'] }}</p>
                                <p class="text-xs text-slate-500 mt-1">In progress</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm p-6 card-hover border border-slate-200">
                        <div class="flex items-center justify-center">
                            <div class="text-center">
                                <div class="text-2xl mb-2">📈</div>
                                <p class="text-sm font-medium text-slate-700">Success Rate</p>
                                @php
                                    $successRate = $userStats['total'] > 0 ? round(($userStats['completed'] / $userStats['total']) * 100) : 0;
                                @endphp
                                <p class="text-xl font-bold text-slate-800">{{ $successRate }}%</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Upload New Document -->
                    <div class="bg-white rounded-xl shadow-sm p-6 card-hover border border-slate-200">
                        <div class="text-center">
                            <div class="text-5xl mb-4 float-animation">📤</div>
                            <h3 class="text-lg font-semibold text-slate-800 mb-2">Upload New Document</h3>
                            <p class="text-slate-600 text-sm mb-4">Transform more documents into cat stories</p>
                            <a href="{{ route('documents.create') }}" 
                               class="inline-flex items-center gradient-bg text-white px-4 py-2 rounded-lg hover:opacity-90 transition-opacity">
                                <span class="mr-2">📄</span>
                                Choose File
                            </a>
                        </div>
                    </div>

                    <!-- View All Documents -->
                    <div class="bg-white rounded-xl shadow-sm p-6 card-hover border border-slate-200">
                        <div class="text-center">
                            <div class="text-5xl mb-4">📁</div>
                            <h3 class="text-lg font-semibold text-slate-800 mb-2">All Documents</h3>
                            <p class="text-slate-600 text-sm mb-4">Manage your uploaded documents</p>
                            <a href="{{ route('documents.index') }}" 
                               class="inline-flex items-center bg-slate-600 text-white px-4 py-2 rounded-lg hover:bg-slate-500 transition-colors">
                                <span class="mr-2">👀</span>
                                View All
                            </a>
                        </div>
                    </div>

                    <!-- Processing Status -->
                    <div class="bg-white rounded-xl shadow-sm p-6 card-hover border border-slate-200">
                        <div class="text-center">
                            @if($userStats['processing'] > 0)
                                <div class="text-5xl mb-4 processing-animation">⏳</div>
                                <h3 class="text-lg font-semibold text-slate-800 mb-2">Currently Processing</h3>
                                <p class="text-slate-600 text-sm mb-4">{{ $userStats['processing'] }} document(s) being transformed</p>
                                <button onclick="window.location.reload()" 
                                        class="inline-flex items-center bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-500 transition-colors">
                                    <span class="mr-2">🔄</span>
                                    Check Status
                                </button>
                            @else
                                <div class="text-5xl mb-4">✅</div>
                                <h3 class="text-lg font-semibold text-slate-800 mb-2">All Caught Up!</h3>
                                <p class="text-slate-600 text-sm mb-4">No documents currently processing</p>
                                <span class="inline-flex items-center bg-green-600 text-white px-4 py-2 rounded-lg">
                                    <span class="mr-2">🎉</span>
                                    Complete
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Recent Documents -->
                @if($userStats['recent']->count() > 0)
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200">
                        <div class="p-6 border-b border-slate-200">
                            <div class="flex justify-between items-center">
                                <h3 class="text-xl font-bold text-slate-800 flex items-center">
                                    <span class="mr-3">📋</span>
                                    Recent Documents
                                </h3>
                                <a href="{{ route('documents.index') }}" 
                                   class="text-slate-600 hover:text-slate-800 text-sm font-medium flex items-center">
                                    View all
                                    <span class="ml-1">→</span>
                                </a>
                            </div>
                        </div>
                        
                        <div class="divide-y divide-slate-200">
                            @foreach($userStats['recent'] as $document)
                                <div class="p-6 hover:bg-slate-50 transition-colors">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center min-w-0 flex-1">
                                            <div class="text-3xl mr-4 flex-shrink-0">
                                                @if($document->file_type === 'pdf') 📄
                                                @elseif(in_array($document->file_type, ['doc', 'docx'])) 📝
                                                @elseif(in_array($document->file_type, ['ppt', 'pptx'])) 📊
                                                @else 📁
                                                @endif
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <h4 class="font-medium text-slate-900 truncate">{{ $document->title }}</h4>
                                                <div class="flex items-center mt-1 text-sm text-slate-500 space-x-4">
                                                    <span>{{ $document->file_size_formatted }}</span>
                                                    <span>{{ $document->created_at->diffForHumans() }}</span>
                                                    @if($document->isCompleted() && $document->processed_at)
                                                        <span class="flex items-center text-green-600">
                                                            <span class="mr-1">⚡</span>
                                                            {{ $document->processed_at->diffForHumans() }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center space-x-4 ml-4">
                                            <!-- Status Badge -->
                                            <span class="status-badge 
                                                @if($document->isCompleted()) status-completed
                                                @elseif($document->isProcessing()) status-processing
                                                @elseif($document->isFailed()) status-failed
                                                @else status-uploaded
                                                @endif
                                            ">
                                                @if($document->isCompleted()) ✅ Complete
                                                @elseif($document->isProcessing()) ⏳ Processing
                                                @elseif($document->isFailed()) ❌ Failed
                                                @else 📤 Uploaded
                                                @endif
                                            </span>
                                            
                                            <!-- Actions -->
                                            <div class="flex items-center space-x-2">
                                                @if($document->hasStory())
                                                    <a href="{{ route('documents.show', $document) }}" 
                                                       class="text-green-600 hover:text-green-800 font-medium text-sm flex items-center">
                                                        <span class="mr-1">🐱</span>
                                                        Story
                                                    </a>
                                                @endif
                                                
                                                <a href="{{ route('documents.show', $document) }}" 
                                                   class="text-slate-600 hover:text-slate-800 text-sm">
                                                    View
                                                </a>
                                                
                                                @if($document->isCompleted())
                                                    <a href="{{ route('documents.download', $document) }}" 
                                                       class="text-slate-600 hover:text-slate-800 text-sm">
                                                        Download
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    
                                    @if($document->isProcessing())
                                        <div class="mt-4">
                                            <div class="flex justify-between items-center mb-2">
                                                <span class="text-sm text-slate-600">Processing progress</span>
                                                <span class="text-sm text-slate-500">Estimated: 1-3 min</span>
                                            </div>
                                            <div class="w-full bg-slate-200 rounded-full h-2">
                                                <div class="bg-yellow-600 h-2 rounded-full processing-animation" style="width: 65%"></div>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    @if($document->isFailed() && $document->error_message)
                                        <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg">
                                            <p class="text-sm text-red-700">
                                                <span class="font-medium">Error:</span> {{ $document->error_message }}
                                            </p>
                                            <div class="mt-2">
                                                <form action="{{ route('documents.retry', $document) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" 
                                                            class="text-sm text-red-600 hover:text-red-800 font-medium">
                                                        Try Again
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Tips and Getting Started -->
                @if($userStats['total'] < 5)
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border border-blue-200 p-6">
                        <div class="flex items-start">
                            <div class="text-4xl mr-4 float-animation">💡</div>
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-slate-800 mb-2">Tips for Better Cat Stories</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <div class="flex items-start">
                                            <span class="text-green-600 mr-2 mt-0.5">✓</span>
                                            <p class="text-sm text-slate-700">Upload clear, well-formatted documents</p>
                                        </div>
                                        <div class="flex items-start">
                                            <span class="text-green-600 mr-2 mt-0.5">✓</span>
                                            <p class="text-sm text-slate-700">PDFs with selectable text work best</p>
                                        </div>
                                        <div class="flex items-start">
                                            <span class="text-green-600 mr-2 mt-0.5">✓</span>
                                            <p class="text-sm text-slate-700">Avoid password-protected files</p>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex items-start">
                                            <span class="text-blue-600 mr-2 mt-0.5">🐱</span>
                                            <p class="text-sm text-slate-700">Complex topics become simple stories</p>
                                        </div>
                                        <div class="flex items-start">
                                            <span class="text-blue-600 mr-2 mt-0.5">🐱</span>
                                            <p class="text-sm text-slate-700">Perfect for study and review</p>
                                        </div>
                                        <div class="flex items-start">
                                            <span class="text-blue-600 mr-2 mt-0.5">🐱</span>
                                            <p class="text-sm text-slate-700">Share stories with classmates</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        // Auto-refresh for processing documents
        @if($userStats['processing'] > 0)
            // Check for processing documents every 30 seconds
            setInterval(function() {
                // Only refresh if user hasn't been active recently
                if (document.hidden === false) {
                    fetch('/documents?json=1')
                        .then(response => response.json())
                        .then(data => {
                            if (data.processing !== {{ $userStats['processing'] }}) {
                                window.location.reload();
                            }
                        })
                        .catch(error => {
                            console.log('Status check failed:', error);
                        });
                }
            }, 30000);
        @endif

        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Animate stats counters
            const counters = document.querySelectorAll('[data-counter]');
            counters.forEach(counter => {
                const target = parseInt(counter.getAttribute('data-counter'));
                const duration = 1000;
                const step = target / (duration / 16);
                let current = 0;
                
                const timer = setInterval(() => {
                    current += step;
                    if (current >= target) {
                        counter.textContent = target;
                        clearInterval(timer);
                    } else {
                        counter.textContent = Math.floor(current);
                    }
                }, 16);
            });
        });
    </script>
    @endpush
</x-app-layout>

<style>
    .gradient-bg {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    }
    
    .card-hover {
        transition: all 0.3s ease;
    }
    
    .card-hover:hover {
        transform: translateY(-2px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    
    .processing-animation {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    
    .float-animation {
        animation: float 3s ease-in-out infinite;
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }
    
    .status-badge {
        @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium;
    }
    
    .status-completed { @apply bg-green-100 text-green-800; }
    .status-processing { @apply bg-yellow-100 text-yellow-800; }
    .status-failed { @apply bg-red-100 text-red-800; }
    .status-uploaded { @apply bg-slate-100 text-slate-800; }
</style>