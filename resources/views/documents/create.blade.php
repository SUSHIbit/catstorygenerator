<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Upload Document') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6 text-center">
                        <h3 class="text-2xl font-bold text-slate-800 mb-2">Upload Your Document 📄</h3>
                        <p class="text-slate-600">Upload a document and let our AI cat narrator transform it into a simple, entertaining story!</p>
                    </div>

                    <!-- Upload Form -->
                    <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data" id="upload-form">
                        @csrf
                        
                        <!-- Custom Title -->
                        <div class="mb-6">
                            <label for="title" class="block text-sm font-medium text-slate-700 mb-2">
                                Document Title (Optional)
                            </label>
                            <input 
                                type="text" 
                                id="title" 
                                name="title" 
                                class="w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                placeholder="Enter a custom title or leave blank to use filename"
                                value="{{ old('title') }}"
                            >
                            @error('title')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- File Upload Area -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-slate-700 mb-2">
                                Upload Document
                            </label>
                            
                            <!-- Drag and Drop Area -->
                            <div 
                                id="drop-area" 
                                class="border-2 border-dashed border-slate-300 rounded-lg p-8 text-center hover:border-slate-400 transition-colors cursor-pointer"
                            >
                                <div id="drop-content">
                                    <div class="text-6xl mb-4">🐱</div>
                                    <h4 class="text-lg font-semibold text-slate-700 mb-2">Drop your document here</h4>
                                    <p class="text-slate-500 mb-4">or click to browse files</p>
                                    <p class="text-sm text-slate-400">Supports: PDF, DOC, DOCX, PPT, PPTX (Max 10MB)</p>
                                </div>
                                
                                <!-- File Selected State -->
                                <div id="file-selected" class="hidden">
                                    <div class="text-4xl mb-4">📄</div>
                                    <p class="text-lg font-medium text-slate-700" id="selected-filename"></p>
                                    <p class="text-sm text-slate-500" id="selected-filesize"></p>
                                    <button type="button" id="remove-file" class="mt-2 text-sm text-red-600 hover:text-red-800">Remove file</button>
                                </div>
                            </div>

                            <input 
                                type="file" 
                                id="file-input" 
                                name="file" 
                                accept=".pdf,.doc,.docx,.ppt,.pptx"
                                class="hidden"
                                required
                            >
                            
                            @error('file')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Supported Formats Info -->
                        <div class="mb-6 bg-slate-50 rounded-lg p-4">
                            <h5 class="font-medium text-slate-700 mb-2">Supported File Types:</h5>
                            <div class="grid grid-cols-2 md:grid-cols-5 gap-2 text-sm text-slate-600">
                                <div class="flex items-center">
                                    <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                    PDF
                                </div>
                                <div class="flex items-center">
                                    <span class="w-2 h-2 bg-blue-500 rounded-full mr-2"></span>
                                    DOC
                                </div>
                                <div class="flex items-center">
                                    <span class="w-2 h-2 bg-blue-600 rounded-full mr-2"></span>
                                    DOCX
                                </div>
                                <div class="flex items-center">
                                    <span class="w-2 h-2 bg-orange-500 rounded-full mr-2"></span>
                                    PPT
                                </div>
                                <div class="flex items-center">
                                    <span class="w-2 h-2 bg-orange-600 rounded-full mr-2"></span>
                                    PPTX
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex items-center justify-between">
                            <a 
                                href="{{ route('documents.index') }}" 
                                class="text-slate-600 hover:text-slate-800 transition-colors"
                            >
                                ← Back to Documents
                            </a>
                            
                            <button 
                                type="submit" 
                                id="submit-btn"
                                class="bg-slate-800 text-white px-6 py-3 rounded-lg hover:bg-slate-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                disabled
                            >
                                <span id="submit-text">Upload Document</span>
                                <span id="submit-loading" class="hidden">
                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Uploading...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dropArea = document.getElementById('drop-area');
            const fileInput = document.getElementById('file-input');
            const dropContent = document.getElementById('drop-content');
            const fileSelected = document.getElementById('file-selected');
            const selectedFilename = document.getElementById('selected-filename');
            const selectedFilesize = document.getElementById('selected-filesize');
            const removeFileBtn = document.getElementById('remove-file');
            const submitBtn = document.getElementById('submit-btn');
            const uploadForm = document.getElementById('upload-form');
            const submitText = document.getElementById('submit-text');
            const submitLoading = document.getElementById('submit-loading');

            // File size formatter
            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }

            // File validation
            function validateFile(file) {
                const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'];
                const allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx'];
                const maxSize = 10 * 1024 * 1024; // 10MB

                const extension = file.name.split('.').pop().toLowerCase();
                
                if (!allowedExtensions.includes(extension)) {
                    alert('Please select a valid file type (PDF, DOC, DOCX, PPT, PPTX)');
                    return false;
                }

                if (file.size > maxSize) {
                    alert('File size must be less than 10MB');
                    return false;
                }

                return true;
            }

            // Handle file selection
            function handleFileSelect(file) {
                if (!validateFile(file)) {
                    return;
                }

                selectedFilename.textContent = file.name;
                selectedFilesize.textContent = formatFileSize(file.size);
                
                dropContent.classList.add('hidden');
                fileSelected.classList.remove('hidden');
                dropArea.classList.add('border-green-300', 'bg-green-50');
                dropArea.classList.remove('border-slate-300');
                
                submitBtn.disabled = false;
            }

            // Remove file
            function removeFile() {
                fileInput.value = '';
                dropContent.classList.remove('hidden');
                fileSelected.classList.add('hidden');
                dropArea.classList.remove('border-green-300', 'bg-green-50');
                dropArea.classList.add('border-slate-300');
                
                submitBtn.disabled = true;
            }

            // Event listeners
            dropArea.addEventListener('click', () => fileInput.click());

            fileInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    handleFileSelect(this.files[0]);
                }
            });

            removeFileBtn.addEventListener('click', removeFile);

            // Drag and drop
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                dropArea.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, unhighlight, false);
            });

            function highlight(e) {
                dropArea.classList.add('border-slate-400', 'bg-slate-50');
            }

            function unhighlight(e) {
                dropArea.classList.remove('border-slate-400', 'bg-slate-50');
            }

            dropArea.addEventListener('drop', handleDrop, false);

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;

                if (files.length > 0) {
                    fileInput.files = files;
                    handleFileSelect(files[0]);
                }
            }

            // Form submission
            uploadForm.addEventListener('submit', function() {
                submitBtn.disabled = true;
                submitText.classList.add('hidden');
                submitLoading.classList.remove('hidden');
            });
        });
    </script>
    @endpush
</x-app-layout>