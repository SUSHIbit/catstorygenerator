<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-slate-900">
                    <div class="text-center mb-8">
                        <h3 class="text-2xl font-bold text-slate-800 mb-4">Welcome to Cat Story Generator! 🐱</h3>
                        <p class="text-slate-600 mb-6">Transform complex documents into simple, entertaining cat stories.</p>
                        
                        <a 
                            href="{{ route('documents.create') }}" 
                            class="bg-slate-800 text-white px-6 py-3 rounded-lg hover:bg-slate-700 transition-colors"
                        >
                            Upload Your First Document
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            @php
                $userStats = [
                    'total' => auth()->user()->documents()->count(),
                    'completed' => auth()->user()->documents()->completed()->count(),
                    'recent' => auth()->user()->getRecentDocuments(3)
                ];
            @endphp

            @if($userStats['total'] > 0)
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="text-3xl">📚</div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-slate-600">Total Documents</p>
                                <p class="text-2xl font-bold text-slate-900">{{ $userStats['total'] }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="text-3xl">🐱</div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-slate-600">Cat Stories</p>
                                <p class="text-2xl font-bold text-slate-900">{{ $userStats['completed'] }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-center">
                            <a 
                                href="{{ route('documents.index') }}" 
                                class="bg-slate-800 text-white px-4 py-2 rounded-lg hover:bg-slate-700 transition-colors"
                            >
                                View All Documents
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Recent Documents -->
                @if($userStats['recent']->count() > 0)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h4 class="text-lg font-medium text-slate-800 mb-4">Recent Documents</h4>
                            <div class="space-y-3">
                                @foreach($userStats['recent'] as $document)
                                    <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                                        <div class="flex items-center">
                                            <div class="text-2xl mr-3">
                                                @if($document->file_type === 'pdf') 📄
                                                @elseif(in_array($document->file_type, ['doc', 'docx'])) 📝
                                                @elseif(in_array($document->file_type, ['ppt', 'pptx'])) 📊
                                                @else 📁
                                                @endif
                                            </div>
                                            <div>
                                                <p class="font-medium text-slate-900">{{ $document->title }}</p>
                                                <p class="text-sm text-slate-500">{{ $document->created_at->diffForHumans() }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-3">
                                            <span class="text-xs px-2 py-1 rounded-full
                                                @if($document->isCompleted()) bg-green-100 text-green-800
                                                @elseif($document->isProcessing()) bg-yellow-100 text-yellow-800
                                                @elseif($document->isFailed()) bg-red-100 text-red-800
                                                @else bg-slate-100 text-slate-800
                                                @endif
                                            ">
                                                {{ ucfirst($document->status) }}
                                            </span>
                                            <a 
                                                href="{{ route('documents.show', $document) }}" 
                                                class="text-slate-600 hover:text-slate-900"
                                            >
                                                View
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-4 text-center">
                                <a 
                                    href="{{ route('documents.index') }}" 
                                    class="text-slate-600 hover:text-slate-800"
                                >
                                    View all documents →
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>