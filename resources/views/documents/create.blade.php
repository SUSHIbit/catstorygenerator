<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center">
            <div>
                <h2 class="font-bold text-2xl text-slate-800 leading-tight flex items-center">
                    <span class="text-3xl mr-3">📤</span>
                    {{ __('Upload Document') }}
                </h2>
                <p class="text-slate-600 mt-1">Transform your documents into entertaining cat stories</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <a href="{{ route('documents.index') }}" 
                   class="inline-flex items-center text-slate-600 hover:text-slate-800 transition-colors">
                    <span class="mr-2">←</span>
                    Back to Documents
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Show upload limits info -->
            @if(isset($uploadLimits))
            <div class="mb-6 bg-blue-50 border border-blue-200 rounded-xl p-4">
                <h4 class="font-medium text-blue-800 mb-2 flex items-center">
                    <span class="mr-2">ℹ️</span>
                    Current Upload Limits
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-blue-700 font-medium">Max File Size:</span>
                        <span class="text-blue-600">{{ $uploadLimits['upload_max_filesize'] }}</span>
                    </div>
                    <div>
                        <span class="text-blue-700 font-medium">Max Post Size:</span>
                        <span class="text-blue-600">{{ $uploadLimits['post_max_size'] }}</span>
                    </div>
                    <div>
                        <span class="text-blue-700 font-medium">Memory Limit:</span>
                        <span class="text-blue-600">{{ $uploadLimits['memory_limit'] }}</span>
                    </div>
                </div>
                @php
                    $maxSizeBytes = min($uploadLimits['upload_max_filesize_bytes'], $uploadLimits['post_max_size_bytes']);
                    $maxSizeMB = round($maxSizeBytes / (1024 * 1024), 1);
                @endphp
                @if($maxSizeMB < 10)
                    <div class="mt-3 p-3 bg-yellow-100 border border-yellow-200 rounded-lg">
                        <p class="text-yellow-800 text-sm">
                            <strong>⚠️ Notice:</strong> Your server's upload limit is {{ $maxSizeMB }}MB. 
                            For larger documents, you may need to increase PHP settings or contact your hosting provider.
                        </p>
                    </div>
                @endif
            </div>
            @endif

            <!-- Progress Steps -->
            <div class="mb-8">
                <div class="flex items-center justify-center space-x-4">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-slate-800 text-white rounded-full flex items-center justify-center text-sm font-medium">1</div>
                        <span class="ml-2 text-sm font-medium text-slate-800">Upload</span>
                    </div>
                    <div class="w-12 h-0.5 bg-slate-300"></div>
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-slate-300 text-slate-500 rounded-full flex items-center justify-center text-sm font-medium">2</div>
                        <span class="ml-2 text-sm text-slate-500">Process</span>
                    </div>
                    <div class="w-12 h-0.5 bg-slate-300"></div>
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-slate-300 text-slate-500 rounded-full flex items-center justify-center text-sm font-medium">3</div>
                        <span class="ml-2 text-sm text-slate-500">Cat Story</span>
                    </div>
                </div>
            </div>

            <!-- Error Display -->
            @if($errors->any())
                <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4">
                    <h4 class="font-medium text-red-800 mb-2 flex items-center">
                        <span class="mr-2">❌</span>
                        Upload Error
                    </h4>
                    <ul class="space-y-1">
                        @foreach($errors->all() as $error)
                            <li class="text-red-700 text-sm">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-slate-200">
                <div class="p-8">
                    <!-- Welcome Section -->
                    <div class="text-center mb-8">
                        <div class="text-6xl mb-4 float-animation">🐱</div>
                        <h3 class="text-2xl font-bold text-slate-800 mb-2">Upload Your Document</h3>
                        <p class="text-slate-600 max-w-2xl mx-auto">
                            Upload any document and our AI cat narrator will transform it into an easy-to-understand story!
                        </p>
                    </div>

                    <!-- Upload Form -->
                    <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data" id="upload-form" data-no-loading>
                        @csrf
                        
                        <!-- Custom Title -->
                        <div class="mb-8">
                            <label for="title" class="block text-sm font-medium text-slate-700 mb-2">
                                Document Title (Optional)
                            </label>
                            <input 
                                type="text" 
                                id="title" 
                                name="title" 
                                class="w-full rounded-xl border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 transition-colors"
                                placeholder="Enter a custom title or leave blank to use filename"
                                value="{{ old('title') }}"
                            >
                            @error('title')
                                <p class="mt-2 text-sm text-red-600 flex items-center">
                                    <span class="mr-2">⚠️</span>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- File Upload Area -->
                        <div class="mb-8">
                            <label class="block text-sm font-medium text-slate-700 mb-4">
                                Choose Document to Upload
                            </label>
                            
                            <!-- Drag and Drop Area -->
                            <div 
                                id="drop-area" 
                                class="file-drop-zone rounded-xl p-12 text-center cursor-pointer transition-all duration-300 border-2 border-dashed border-slate-300 hover:border-slate-400 hover:bg-slate-50"
                            >
                                <div id="drop-content">
                                    <div class="text-8xl mb-6 float-animation">📁</div>
                                    <h4 class="text-xl font-semibold text-slate-700 mb-3">Drop your document here</h4>
                                    <p class="text-slate-500 mb-6 text-lg">or click to browse files</p>
                                    <div class="inline-flex items-center gradient-bg text-white px-6 py-3 rounded-xl hover:opacity-90 transition-opacity">
                                        <span class="mr-2">📄</span>
                                        Choose File
                                    </div>
                                    <p class="text-sm text-slate-400 mt-4">
                                        Supports: PDF, DOC, DOCX, PPT, PPTX 
                                        @if(isset($uploadLimits))
                                            (Max: {{ min($uploadLimits['upload_max_filesize'], $uploadLimits['post_max_size']) }})
                                        @endif
                                    </p>
                                </div>
                                
                                <!-- File Selected State -->
                                <div id="file-selected" class="hidden">
                                    <div class="text-6xl mb-4">✅</div>
                                    <p class="text-xl font-medium text-slate-700 mb-2" id="selected-filename">document.pdf</p>
                                    <p class="text-sm text-slate-500 mb-6" id="selected-filesize">2.5 MB</p>
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-center space-x-4">
                                            <button type="submit" id="process-btn" 
                                                    class="gradient-bg text-white px-6 py-3 rounded-xl hover:opacity-90 transition-opacity font-medium">
                                                <span class="mr-2">🚀</span>
                                                Process Document
                                            </button>
                                            <button type="button" id="remove-file" 
                                                    class="text-slate-600 hover:text-slate-800 px-4 py-2 border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors">
                                                Remove file
                                            </button>
                                        </div>
                                        <div id="file-preview" class="bg-slate-50 rounded-xl p-4 text-left max-w-md mx-auto">
                                            <h5 class="font-medium text-slate-700 mb-2">File Details:</h5>
                                            <div class="space-y-1 text-sm text-slate-600">
                                                <div class="flex justify-between">
                                                    <span>Type:</span>
                                                    <span id="file-type">PDF</span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span>Size:</span>
                                                    <span id="file-size-display">2.5 MB</span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span>Est. processing:</span>
                                                    <span id="processing-time">1-3 minutes</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Upload Progress -->
                                <div id="upload-progress" class="hidden">
                                    <div class="text-6xl mb-4 processing-animation">⏳</div>
                                    <h4 class="text-xl font-medium text-slate-700 mb-2">Uploading Document...</h4>
                                    <div class="w-full bg-slate-200 rounded-full h-3 mb-4 max-w-md mx-auto">
                                        <div id="progress-bar" class="gradient-bg h-3 rounded-full transition-all duration-300" style="width: 0%"></div>
                                    </div>
                                    <p class="text-sm text-slate-500" id="upload-status">Preparing upload...</p>
                                </div>

                                <!-- Size Warning -->
                                <div id="size-warning" class="hidden mt-4 p-3 bg-yellow-100 border border-yellow-200 rounded-lg">
                                    <p class="text-yellow-800 text-sm">
                                        <strong>⚠️ Large File:</strong> This file may take longer to upload and process.
                                    </p>
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
                                <p class="mt-2 text-sm text-red-600 flex items-center">
                                    <span class="mr-2">⚠️</span>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Supported Formats Info -->
                        <div class="bg-slate-50 rounded-xl p-6 mb-8">
                            <h5 class="font-medium text-slate-700 mb-4 flex items-center">
                                <span class="mr-2">📋</span>
                                Supported File Types & Requirements
                            </h5>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h6 class="font-medium text-slate-700 mb-3">File Types:</h6>
                                    <div class="space-y-2">
                                        <div class="flex items-center text-sm">
                                            <div class="w-3 h-3 bg-red-500 rounded-full mr-3"></div>
                                            <span class="font-medium mr-2">PDF</span>
                                            <span class="text-slate-600">- Portable Document Format</span>
                                        </div>
                                        <div class="flex items-center text-sm">
                                            <div class="w-3 h-3 bg-blue-500 rounded-full mr-3"></div>
                                            <span class="font-medium mr-2">DOC/DOCX</span>
                                            <span class="text-slate-600">- Microsoft Word Documents</span>
                                        </div>
                                        <div class="flex items-center text-sm">
                                            <div class="w-3 h-3 bg-orange-500 rounded-full mr-3"></div>
                                            <span class="font-medium mr-2">PPT/PPTX</span>
                                            <span class="text-slate-600">- PowerPoint Presentations</span>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="font-medium text-slate-700 mb-3">Requirements:</h6>
                                    <div class="space-y-2 text-sm text-slate-600">
                                        @if(isset($uploadLimits))
                                            @php
                                                $maxSizeBytes = min($uploadLimits['upload_max_filesize_bytes'], $uploadLimits['post_max_size_bytes']);
                                                $maxSizeMB = round($maxSizeBytes / (1024 * 1024), 1);
                                            @endphp
                                            <div class="flex items-start">
                                                <span class="text-blue-600 mr-2 mt-0.5">📏</span>
                                                <span>Maximum file size: {{ $maxSizeMB }}MB</span>
                                            </div>
                                        @endif
                                        <div class="flex items-start">
                                            <span class="text-green-600 mr-2 mt-0.5">✓</span>
                                            <span>Text must be selectable (not scanned images)</span>
                                        </div>
                                        <div class="flex items-start">
                                            <span class="text-green-600 mr-2 mt-0.5">✓</span>
                                            <span>No password protection</span>
                                        </div>
                                        <div class="flex items-start">
                                            <span class="text-green-600 mr-2 mt-0.5">✓</span>
                                            <span>At least 10 characters of content</span>
                                        </div>
                                        <div class="flex items-start">
                                            <span class="text-blue-600 mr-2 mt-0.5">💡</span>
                                            <span>Large documents may take several minutes to process</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Troubleshooting Section -->
                        @if(isset($uploadLimits) && min($uploadLimits['upload_max_filesize_bytes'], $uploadLimits['post_max_size_bytes']) < 10485760)
                        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
                            <h5 class="font-medium text-yellow-800 mb-4 flex items-center">
                                <span class="mr-2">⚠️</span>
                                Upload Limit Notice
                            </h5>
                            <p class="text-yellow-700 mb-4">
                                Your server currently has a {{ round(min($uploadLimits['upload_max_filesize_bytes'], $uploadLimits['post_max_size_bytes']) / (1024 * 1024), 1) }}MB upload limit. 
                                If you need to upload larger files, you may need to:
                            </p>
                            <ul class="text-yellow-700 text-sm space-y-1 ml-4">
                                <li>• Contact your hosting provider to increase upload limits</li>
                                <li>• Compress your document before uploading</li>
                                <li>• Split large documents into smaller sections</li>
                            </ul>
                        </div>
                        @endif

                        <!-- What Happens Next -->
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border border-blue-200 p-6">
                            <h5 class="font-medium text-slate-800 mb-4 flex items-center">
                                <span class="mr-2">🔮</span>
                                What happens after upload?
                            </h5>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="text-center">
                                    <div class="text-3xl mb-2">🔧</div>
                                    <h6 class="font-medium text-slate-700 mb-1">Text Extraction</h6>
                                    <p class="text-sm text-slate-600">We extract all readable text from your document</p>
                                </div>
                                <div class="text-center">
                                    <div class="text-3xl mb-2">🤖</div>
                                    <h6 class="font-medium text-slate-700 mb-1">AI Analysis</h6>
                                    <p class="text-sm text-slate-600">Our AI cat narrator reads and understands the content</p>
                                </div>
                                <div class="text-center">
                                    <div class="text-3xl mb-2">🐱</div>
                                    <h6 class="font-medium text-slate-700 mb-1">Cat Story Creation</h6>
                                    <p class="text-sm text-slate-600">Complex ideas become simple, fun cat stories!</p>
                                </div>
                            </div>
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
            const uploadProgress = document.getElementById('upload-progress');
            const selectedFilename = document.getElementById('selected-filename');
            const selectedFilesize = document.getElementById('selected-filesize');
            const removeFileBtn = document.getElementById('remove-file');
            const uploadForm = document.getElementById('upload-form');
            const sizeWarning = document.getElementById('size-warning');

            // Get server limits from PHP
            const serverLimits = @json($uploadLimits ?? ['upload_max_filesize_bytes' => 2097152, 'post_max_size_bytes' => 8388608]);
            const maxUploadSize = Math.min(serverLimits.upload_max_filesize_bytes || 2097152, serverLimits.post_max_size_bytes || 8388608);

            // File validation with server limits
            function validateFile(file) {
                const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'];
                const allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx'];

                const extension = file.name.split('.').pop().toLowerCase();
                
                if (!allowedExtensions.includes(extension)) {
                    CatStoryApp.showNotification('Please select a valid file type (PDF, DOC, DOCX, PPT, PPTX)', 'error');
                    return false;
                }

                // Check file size against server limits
                if (file.size > maxUploadSize) {
                    const maxSizeMB = (maxUploadSize / (1024 * 1024)).toFixed(1);
                    const fileSizeMB = (file.size / (1024 * 1024)).toFixed(1);
                    CatStoryApp.showNotification(`File size (${fileSizeMB}MB) exceeds server limit (${maxSizeMB}MB)`, 'error');
                    return false;
                }

                return true;
            }

            // Estimate processing time
            function estimateProcessingTime(fileSize) {
                if (fileSize < 1024 * 1024) return '1-2 minutes'; // < 1MB
                if (fileSize < 5 * 1024 * 1024) return '2-3 minutes'; // < 5MB
                if (fileSize < 10 * 1024 * 1024) return '3-5 minutes'; // < 10MB
                if (fileSize < 50 * 1024 * 1024) return '5-10 minutes'; // < 50MB
                return '10+ minutes'; // Very large files
            }

            // Handle file selection
            function handleFileSelect(file) {
                if (!validateFile(file)) {
                    return;
                }

                const fileType = file.name.split('.').pop().toUpperCase();
                const formattedSize = CatStoryApp.formatFileSize(file.size);
                const processingTime = estimateProcessingTime(file.size);

                selectedFilename.textContent = file.name;
                selectedFilesize.textContent = formattedSize;
                document.getElementById('file-type').textContent = fileType;
                document.getElementById('file-size-display').textContent = formattedSize;
                document.getElementById('processing-time').textContent = processingTime;
                
                dropContent.classList.add('hidden');
                fileSelected.classList.remove('hidden');
                dropArea.classList.add('border-green-300', 'bg-green-50');
                dropArea.classList.remove('border-slate-300');
                
                // Show size warning for large files
                if (file.size > 10 * 1024 * 1024) { // > 10MB
                    sizeWarning.classList.remove('hidden');
                } else {
                    sizeWarning.classList.add('hidden');
                }
                
                // Auto-fill title if empty
                const titleInput = document.getElementById('title');
                if (!titleInput.value) {
                    const nameWithoutExt = file.name.replace(/\.[^/.]+$/, "");
                    titleInput.value = nameWithoutExt;
                }
            }

            // Remove file
            function removeFile() {
                fileInput.value = '';
                document.getElementById('title').value = '';
                dropContent.classList.remove('hidden');
                fileSelected.classList.add('hidden');
                uploadProgress.classList.add('hidden');
                sizeWarning.classList.add('hidden');
                dropArea.classList.remove('border-green-300', 'bg-green-50');
                dropArea.classList.add('border-slate-300');
            }

            // Event listeners
            dropArea.addEventListener('click', () => {
                if (!fileSelected.classList.contains('hidden')) return;
                fileInput.click();
            });

            fileInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    handleFileSelect(this.files[0]);
                }
            });

            removeFileBtn.addEventListener('click', removeFile);

            // Drag and drop functionality
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, preventDefaults, false);
                document.body.addEventListener(eventName, preventDefaults, false);
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
                dropArea.classList.add('border-blue-400', 'bg-blue-50');
            }

            function unhighlight(e) {
                dropArea.classList.remove('border-blue-400', 'bg-blue-50');
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

            // Enhanced form submission with better error handling
            uploadForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (!fileInput.files.length) {
                    CatStoryApp.showNotification('Please select a file first', 'error');
                    return;
                }

                const file = fileInput.files[0];
                
                // Final validation before upload
                if (!validateFile(file)) {
                    return;
                }

                // Show upload progress
                fileSelected.classList.add('hidden');
                uploadProgress.classList.remove('hidden');
                
                // Progress simulation with more realistic timing
                let progress = 0;
                const maxProgress = 90; // Don't reach 100% until actually complete
                const fileSize = file.size;
                const progressSpeed = fileSize > 10 * 1024 * 1024 ? 800 : 400; // Slower for large files
                
                const progressInterval = setInterval(() => {
                    const increment = Math.random() * 8 + 2; // 2-10% increments
                    progress += increment;
                    
                    if (progress > maxProgress) {
                        progress = maxProgress;
                        clearInterval(progressInterval);
                    }
                    
                    document.getElementById('progress-bar').style.width = progress + '%';
                    
                    // Update status messages
                    if (progress < 20) {
                        document.getElementById('upload-status').textContent = 'Uploading file...';
                    } else if (progress < 50) {
                        document.getElementById('upload-status').textContent = 'Validating document...';
                    } else if (progress < 80) {
                        document.getElementById('upload-status').textContent = 'Processing file...';
                    } else {
                        document.getElementById('upload-status').textContent = 'Almost complete...';
                    }
                }, progressSpeed);

                // Submit the actual form
                this.submit();
            });

            // Prevent accidental page navigation during upload
            let uploading = false;
            
            uploadForm.addEventListener('submit', () => {
                uploading = true;
            });
            
            window.addEventListener('beforeunload', (e) => {
                if (uploading) {
                    e.preventDefault();
                    e.returnValue = 'Upload in progress. Are you sure you want to leave?';
                }
            });
        });
    </script>
    @endpush
</x-app-layout>

<style>
    .gradient-bg {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    }
    
    .float-animation {
        animation: float 3s ease-in-out infinite;
    }
    
    .processing-animation {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }
    
    .file-drop-zone {
        transition: all 0.3s ease;
    }
</style>