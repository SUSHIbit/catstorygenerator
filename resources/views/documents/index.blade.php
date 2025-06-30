<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('My Documents') }}
            </h2>
            <a 
                href="{{ route('documents.create') }}" 
                class="bg-slate-800 text-white px-4 py-2 rounded-lg hover:bg-slate-700 transition-colors"
            >
                Upload Document
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="text-3xl">📚</div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-slate-600">Total Documents</p>
                            <p class="text-2xl font-bold text-slate-900">{{ $stats['total'] }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="text-3xl">✅</div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-slate-600">Completed</p>
                            <p class="text-2xl font-bold text-green-600">{{ $stats['completed'] }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="text-3xl">⏳</div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-slate-600">Processing</p>
                            <p class="text-2xl font-bold text-yellow-600">{{ $stats['processing'] }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="text-3xl">❌</div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-slate-600">Failed</p>
                            <p class="text-2xl font-bold text-red-600">{{ $stats['failed'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents List -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if($documents->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                            Document
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                            Type
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                            Size
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                            Uploaded
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-slate-200">
                                    @foreach($documents as $document)
                                        <tr class="hover:bg-slate-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="text-2xl mr-3">
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
                                                    <div>
                                                        <div class="text-sm font-medium text-slate-900">
                                                            {{ $document->title }}
                                                        </div>
                                                        <div class="text-sm text-slate-500">
                                                            {{ $document->filename }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                    @if($document->file_type === 'pdf') bg-red-100 text-red-800
                                                    @elseif(in_array($document->file_type, ['doc', 'docx'])) bg-blue-100 text-blue-800
                                                    @elseif(in_array($document->file_type, ['ppt', 'pptx'])) bg-orange-100 text-orange-800
                                                    @else bg-slate-100 text-slate-800
                                                    @endif
                                                ">
                                                    {{ strtoupper($document->file_type) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                                {{ $document->file_size_formatted }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($document->status === 'completed')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        ✅ Completed
                                                    </span>
                                                @elseif($document->status === 'processing')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                        ⏳ Processing
                                                    </span>
                                                @elseif($document->status === 'failed')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        ❌ Failed
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800">
                                                        📤 Uploaded
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                                {{ $document->created_at->format('M j, Y') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div class="flex items-center justify-end space-x-2">
                                                    <a href="{{ route('documents.show', $document) }}" class="text-slate-600 hover:text-slate-900">
                                                        View
                                                    </a>
                                                    @if($document->isCompleted())
                                                        <a href="{{ route('documents.download', $document) }}" class="text-slate-600 hover:text-slate-900">
                                                            Download
                                                        </a>
                                                    @endif
                                                    <form action="{{ route('documents.destroy', $document) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button 
                                                            type="submit" 
                                                            class="text-red-600 hover:text-red-900"
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

                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $documents->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <div class="text-6xl mb-4">🐱</div>
                            <h3 class="text-lg font-medium text-slate-900 mb-2">No documents yet</h3>
                            <p class="text-slate-500 mb-6">Upload your first document to get started with cat stories!</p>
                            <a 
                                href="{{ route('documents.create') }}" 
                                class="bg-slate-800 text-white px-6 py-3 rounded-lg hover:bg-slate-700 transition-colors"
                            >
                                Upload Your First Document
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>