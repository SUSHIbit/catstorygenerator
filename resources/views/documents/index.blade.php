<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center">
            <div>
                <h2 class="font-bold text-2xl text-slate-800 leading-tight flex items-center">
                    <span class="text-3xl mr-3">📚</span>
                    {{ __('My Documents') }}
                </h2>
                <p class="text-slate-600 mt-1">Manage your uploaded documents and cat stories</p>
            </div>
            <div class="mt-4 lg:mt-0 flex items-center space-x-3">
                <div class="text-sm text-slate-600">
                    {{ $documents->total() }} document{{ $documents->total() !== 1 ? 's' : '' }} total
                </div>
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
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
                <div class="bg-white rounded-xl shadow-sm p-4 lg:p-6 card-hover border border-slate-200">
                    <div class="flex items-center">
                        <div class="text-3xl lg:text-4xl mr-3 lg:mr-4">📚</div>
                        <div>
                            <p class="text-slate-600 text-xs lg:text-sm font-medium">Total</p>
                            <p class="text-xl lg:text-2xl font-bold text-slate-800">{{ $stats['total'] }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-sm p-4 lg:p-6 card-hover border border-slate-200">
                    <div class="flex items-center">
                        <div class="text-3xl lg:text-4xl mr-3 lg:mr-4">✅</div>
                        <div>
                            <p class="text-slate-600 text-xs lg:text-sm font-medium">Completed</p>
                            <p class="text-xl lg:text-2xl font-bold text-green-600">{{ $stats['completed'] }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-sm p-4 lg:p-6 card-hover border border-slate-200">
                    <div class="flex items-center">
                        <div class="text-3xl lg:text-4xl mr-3 lg:mr-4">⏳</div>
                        <div>
                            <p class="text-slate-600 text-xs lg:text-sm font-medium">Processing</p>
                            <p class="text-xl lg:text-2xl font-bold text-yellow-600">{{ $stats['processing'] }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-sm p-4 lg:p-6 card-hover border border-slate-200">
                    <div class="flex items-center">
                        <div class="text-3xl lg:text-4xl mr-3 lg:mr-4">❌</div>
                        <div>
                            <p class="text-slate-600 text-xs lg:text-sm font-medium">Failed</p>
                            <p class="text-xl lg:text-2xl font-bold text-red-600">{{ $stats['failed'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
                    <!-- Search -->
                    <div class="flex-1 max-w-md">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-slate-400">🔍</span>
                            </div>
                            <input 
                                type="search" 
                                id="search-documents"
                                placeholder="Search documents..." 
                                class="block w-full pl-10 pr-3 py-2 border border-slate-300 rounded-lg focus:ring-slate-500 focus:border-slate-500 transition-colors"
                                value="{{ request('search') }}"
                            >
                        </div>
                    </div>
                    
                    <!-- Filters -->
                    <div class="flex items-center space-x-3">
                        <!-- Status Filter -->
                        <select id="status-filter" class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-slate-500 focus:border-slate-500">
                            <option value="">All Status</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>✅ Completed</option>
                            <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>⏳ Processing</option>
                            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>❌ Failed</option>
                            <option value="uploaded" {{ request('status') === 'uploaded' ? 'selected' : '' }}>📤 Uploaded</option>
                        </select>
                        
                        <!-- File Type Filter -->
                        <select id="type-filter" class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-slate-500 focus:border-slate-500">
                            <option value="">All Types</option>
                            <option value="pdf" {{ request('type') === 'pdf' ? 'selected' : '' }}>📄 PDF</option>
                            <option value="doc,docx" {{ request('type') === 'doc,docx' ? 'selected' : '' }}>📝 Word</option>
                            <option value="ppt,pptx" {{ request('type') === 'ppt,pptx' ? 'selected' : '' }}>📊 PowerPoint</option>
                        </select>
                        
                        <!-- Sort -->
                        <select id="sort-filter" class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-slate-500 focus:border-slate-500">
                            <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>📅 Newest First</option>
                            <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>📅 Oldest First</option>
                            <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>🔤 Name A-Z</option>
                            <option value="size" {{ request('sort') === 'size' ? 'selected' : '' }}>📏 File Size</option>
                        </select>
                        
                        <!-- View Toggle -->
                        <div class="flex items-center bg-slate-100 rounded-lg p-1">
                            <button id="grid-view" class="p-2 rounded-md text-slate-600 hover:text-slate-800 hover:bg-white transition-colors" title="Grid View">
                                ⊞
                            </button>
                            <button id="list-view" class="p-2 rounded-md text-slate-600 hover:text-slate-800 hover:bg-white transition-colors bg-white text-slate-800" title="List View">
                                ☰
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents Display -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200">
                @if($documents->count() > 0)
                    <!-- List View (Default) -->
                    <div id="list-container" class="">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                            Document
                                        </th>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                            Details
                                        </th>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                            Created
                                        </th>
                                        <th class="px-6 py-4 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-slate-200">
                                    @foreach($documents as $document)
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center">
                                                    <div class="text-2xl mr-3 flex-shrink-0">
                                                        @if($document->file_type === 'pdf')
                                                            📄
                                                        @elseif(in_array($document->file_type, ['doc', 'docx']))
                                                            📝
                                                        @elseif(in_array($document->file_type, ['ppt', 'pptx']))
                                                            📊
                                                        @else
                                                            📁
                                                        @endif
                                                    </div>
                                                    <div class="min-w-0 flex-1">
                                                        <p class="text-sm font-medium text-slate-900 truncate">
                                                            {{ $document->title }}
                                                        </p>
                                                        <p class="text-sm text-slate-500 truncate">
                                                            {{ $document->filename }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-slate-600">
                                                    <div class="flex items-center space-x-3">
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                                            @if($document->file_type === 'pdf') bg-red-100 text-red-800
                                                            @elseif(in_array($document->file_type, ['doc', 'docx'])) bg-blue-100 text-blue-800
                                                            @elseif(in_array($document->file_type, ['ppt', 'pptx'])) bg-orange-100 text-orange-800
                                                            @else bg-slate-100 text-slate-800
                                                            @endif
                                                        ">
                                                            {{ strtoupper($document->file_type) }}
                                                        </span>
                                                        <span>{{ $document->file_size_formatted }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="status-badge 
                                                    @if($document->status === 'completed') status-completed
                                                    @elseif($document->status === 'processing') status-processing
                                                    @elseif($document->status === 'failed') status-failed
                                                    @else status-uploaded
                                                    @endif
                                                ">
                                                    @if($document->status === 'completed')
                                                        ✅ Completed
                                                    @elseif($document->status === 'processing')
                                                        ⏳ Processing
                                                    @elseif($document->status === 'failed')
                                                        ❌ Failed
                                                    @else
                                                        📤 Uploaded
                                                    @endif
                                                </span>
                                                @if($document->hasStory())
                                                    <div class="mt-1">
                                                        <span class="inline-flex items-center text-xs text-green-600">
                                                            🐱 Story Ready
                                                        </span>
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-sm text-slate-500">
                                                <div>{{ $document->created_at->format('M j, Y') }}</div>
                                                <div class="text-xs text-slate-400">{{ $document->created_at->format('g:i A') }}</div>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <div class="flex items-center justify-end space-x-2">
                                                    @if($document->hasStory())
                                                        <a href="{{ route('documents.show', $document) }}" 
                                                           class="text-green-600 hover:text-green-800 text-sm font-medium flex items-center">
                                                            <span class="mr-1">🐱</span>
                                                            Story
                                                        </a>
                                                    @endif
                                                    
                                                    <a href="{{ route('documents.show', $document) }}" 
                                                       class="text-slate-600 hover:text-slate-900 text-sm">
                                                        View
                                                    </a>
                                                    
                                                    @if($document->isCompleted())
                                                        <a href="{{ route('documents.download', $document) }}" 
                                                           class="text-slate-600 hover:text-slate-900 text-sm">
                                                            Download
                                                        </a>
                                                    @endif
                                                    
                                                    <form action="{{ route('documents.destroy', $document) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button 
                                                            type="submit" 
                                                            class="text-red-600 hover:text-red-900 text-sm"
                                                            onclick="return confirm('Are you sure you want to delete this document?')"
                                                        >
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Grid View -->
                    <div id="grid-container" class="hidden p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                            @foreach($documents as $document)
                                <div class="bg-slate-50 rounded-xl p-6 card-hover border border-slate-200">
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex items-center min-w-0 flex-1">
                                            <div class="text-3xl mr-3 flex-shrink-0">
                                                @if($document->file_type === 'pdf') 📄
                                                @elseif(in_array($document->file_type, ['doc', 'docx'])) 📝
                                                @elseif(in_array($document->file_type, ['ppt', 'pptx'])) 📊
                                                @else 📁
                                                @endif
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <h4 class="font-medium text-slate-800 truncate">{{ $document->title }}</h4>
                                                <p class="text-sm text-slate-500 truncate">{{ $document->filename }}</p>
                                            </div>
                                        </div>
                                        <span class="status-badge 
                                            @if($document->status === 'completed') status-completed
                                            @elseif($document->status === 'processing') status-processing
                                            @elseif($document->status === 'failed') status-failed
                                            @else status-uploaded
                                            @endif
                                        ">
                                            @if($document->status === 'completed') ✅
                                            @elseif($document->status === 'processing') ⏳
                                            @elseif($document->status === 'failed') ❌
                                            @else 📤
                                            @endif
                                        </span>
                                    </div>
                                    
                                    <div class="text-sm text-slate-600 mb-4 space-y-1">
                                        <div class="flex justify-between">
                                            <span>Type:</span>
                                            <span class="font-medium">{{ strtoupper($document->file_type) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span>Size:</span>
                                            <span>{{ $document->file_size_formatted }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span>Created:</span>
                                            <span>{{ $document->created_at->format('M j, Y') }}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="flex justify-between items-center">
                                        @if($document->hasStory())
                                            <a href="{{ route('documents.show', $document) }}" 
                                               class="text-green-600 hover:text-green-800 text-sm font-medium flex items-center">
                                                <span class="mr-1">🐱</span>
                                                View Story
                                            </a>
                                        @else
                                            <span class="text-slate-400 text-sm">No story yet</span>
                                        @endif
                                        
                                        <div class="flex space-x-2">
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
                                            <form action="{{ route('documents.destroy', $document) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button 
                                                    type="submit" 
                                                    class="text-red-600 hover:text-red-900 text-sm"
                                                    onclick="return confirm('Are you sure?')"
                                                >
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Pagination -->
                    @if($documents->hasPages())
                        <div class="px-6 py-4 border-t border-slate-200">
                            {{ $documents->appends(request()->query())->links() }}
                        </div>
                    @endif
                @else
                    <!-- Empty State -->
                    <div class="text-center py-16">
                        <div class="text-8xl mb-6 float-animation">🐱</div>
                        @if(request()->hasAny(['search', 'status', 'type']))
                            <h3 class="text-xl font-medium text-slate-900 mb-2">No documents found</h3>
                            <p class="text-slate-500 mb-6">Try adjusting your filters or search terms</p>
                            <button onclick="clearFilters()" 
                                    class="inline-flex items-center bg-slate-600 text-white px-4 py-2 rounded-lg hover:bg-slate-500 transition-colors">
                                <span class="mr-2">🔄</span>
                                Clear Filters
                            </button>
                        @else
                            <h3 class="text-xl font-medium text-slate-900 mb-2">No documents yet</h3>
                            <p class="text-slate-500 mb-6">Upload your first document to get started with cat stories!</p>
                            <a href="{{ route('documents.create') }}" 
                               class="inline-flex items-center gradient-bg text-white px-6 py-3 rounded-xl hover:opacity-90 transition-opacity font-medium">
                                <span class="mr-2">📤</span>
                                Upload Your First Document
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search-documents');
            const statusFilter = document.getElementById('status-filter');
            const typeFilter = document.getElementById('type-filter');
            const sortFilter = document.getElementById('sort-filter');
            const gridViewBtn = document.getElementById('grid-view');
            const listViewBtn = document.getElementById('list-view');
            const gridContainer = document.getElementById('grid-container');
            const listContainer = document.getElementById('list-container');

            // Search and filter functionality
            let searchTimeout;
            
            function applyFilters() {
                const params = new URLSearchParams();
                
                if (searchInput.value) params.set('search', searchInput.value);
                if (statusFilter.value) params.set('status', statusFilter.value);
                if (typeFilter.value) params.set('type', typeFilter.value);
                if (sortFilter.value) params.set('sort', sortFilter.value);
                
                window.location.href = window.location.pathname + '?' + params.toString();
            }

            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(applyFilters, 500);
            });

            [statusFilter, typeFilter, sortFilter].forEach(filter => {
                filter.addEventListener('change', applyFilters);
            });

            // View toggle
            function switchToGrid() {
                gridContainer.classList.remove('hidden');
                listContainer.classList.add('hidden');
                gridViewBtn.classList.add('bg-white', 'text-slate-800');
                listViewBtn.classList.remove('bg-white', 'text-slate-800');
                localStorage.setItem('documentsView', 'grid');
            }

            function switchToList() {
                listContainer.classList.remove('hidden');
                gridContainer.classList.add('hidden');
                listViewBtn.classList.add('bg-white', 'text-slate-800');
                gridViewBtn.classList.remove('bg-white', 'text-slate-800');
                localStorage.setItem('documentsView', 'list');
            }

            gridViewBtn.addEventListener('click', switchToGrid);
            listViewBtn.addEventListener('click', switchToList);

            // Restore saved view preference
            const savedView = localStorage.getItem('documentsView');
            if (savedView === 'grid') {
                switchToGrid();
            }

            // Clear filters function
            window.clearFilters = function() {
                window.location.href = window.location.pathname;
            };

            // Auto-refresh for processing documents
            @if($stats['processing'] > 0)
                setInterval(function() {
                    if (!document.hidden) {
                        // Only refresh if there are processing documents
                        const processingElements = document.querySelectorAll('.status-processing');
                        if (processingElements.length > 0) {
                            window.location.reload();
                        }
                    }
                }, 30000); // Check every 30 seconds
            @endif

            // Bulk actions (future enhancement)
            let selectedDocuments = new Set();
            
            function toggleDocumentSelection(documentId) {
                if (selectedDocuments.has(documentId)) {
                    selectedDocuments.delete(documentId);
                } else {
                    selectedDocuments.add(documentId);
                }
                updateBulkActions();
            }
            
            function updateBulkActions() {
                const bulkActionsBar = document.getElementById('bulk-actions');
                if (bulkActionsBar) {
                    if (selectedDocuments.size > 0) {
                        bulkActionsBar.classList.remove('hidden');
                        document.getElementById('selected-count').textContent = selectedDocuments.size;
                    } else {
                        bulkActionsBar.classList.add('hidden');
                    }
                }
            }

            // Add keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Ctrl/Cmd + K to focus search
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    searchInput.focus();
                }
                
                // G for grid view, L for list view
                if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'SELECT') {
                    if (e.key === 'g' || e.key === 'G') {
                        switchToGrid();
                    } else if (e.key === 'l' || e.key === 'L') {
                        switchToList();
                    }
                }
            });

            // Add tooltips for view buttons
            gridViewBtn.title = 'Grid View (G)';
            listViewBtn.title = 'List View (L)';
            
            // Add search shortcut hint
            searchInput.placeholder = 'Search documents... (Ctrl+K)';
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

    /* Custom scrollbar for table */
    .overflow-x-auto::-webkit-scrollbar {
        height: 6px;
    }
    
    .overflow-x-auto::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 3px;
    }
    
    .overflow-x-auto::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }
    
    .overflow-x-auto::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>