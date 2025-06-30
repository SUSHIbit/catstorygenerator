<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Cat Story Generator') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <style>
            body { font-family: 'Inter', sans-serif; }
            
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
            
            .file-drop-zone {
                border: 2px dashed #cbd5e1;
                transition: all 0.3s ease;
            }
            
            .file-drop-zone.drag-over {
                border-color: #3b82f6;
                background-color: #eff6ff;
            }
            
            .status-badge {
                @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium;
            }
            
            .status-completed { @apply bg-green-100 text-green-800; }
            .status-processing { @apply bg-yellow-100 text-yellow-800; }
            .status-failed { @apply bg-red-100 text-red-800; }
            .status-uploaded { @apply bg-slate-100 text-slate-800; }
        </style>
    </head>
    <body class="font-sans antialiased bg-slate-50">
        <div class="min-h-screen">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white shadow-sm border-b border-slate-200">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Flash Messages -->
            @if (session('success'))
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
                    <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <span class="text-green-400 text-xl">✅</span>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">
                                    {{ session('success') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
                    <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <span class="text-red-400 text-xl">❌</span>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800">
                                    {{ session('error') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if (session('info'))
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <span class="text-blue-400 text-xl">ℹ️</span>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-blue-800">
                                    {{ session('info') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        <!-- Loading Overlay Component -->
        <div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
            <div class="flex items-center justify-center min-h-screen">
                <div class="bg-white rounded-xl p-8 max-w-sm w-full mx-4">
                    <div class="text-center">
                        <div class="text-6xl mb-4 float-animation">🐱</div>
                        <h3 class="text-lg font-semibold text-slate-800 mb-2">Processing...</h3>
                        <p class="text-slate-600 text-sm mb-4">Please wait while we process your request</p>
                        <div class="w-full bg-slate-200 rounded-full h-2">
                            <div class="bg-slate-800 h-2 rounded-full processing-animation" style="width: 60%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @stack('scripts')

        <script>
            // Global JavaScript utilities
            window.CatStoryApp = {
                showLoading: function() {
                    document.getElementById('loadingOverlay').classList.remove('hidden');
                },
                
                hideLoading: function() {
                    document.getElementById('loadingOverlay').classList.add('hidden');
                },
                
                formatFileSize: function(bytes) {
                    if (bytes === 0) return '0 Bytes';
                    const k = 1024;
                    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
                },
                
                showNotification: function(message, type = 'success') {
                    const notification = document.createElement('div');
                    const bgColor = type === 'success' ? 'bg-green-600' : type === 'error' ? 'bg-red-600' : 'bg-blue-600';
                    
                    notification.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-xl shadow-lg z-50 transition-all transform translate-x-full opacity-0`;
                    notification.textContent = message;
                    
                    document.body.appendChild(notification);
                    
                    setTimeout(() => {
                        notification.classList.remove('translate-x-full', 'opacity-0');
                    }, 100);
                    
                    setTimeout(() => {
                        notification.classList.add('translate-x-full', 'opacity-0');
                        setTimeout(() => notification.remove(), 300);
                    }, 3000);
                }
            };

            // Auto-hide flash messages
            setTimeout(() => {
                document.querySelectorAll('[class*="bg-green-50"], [class*="bg-red-50"], [class*="bg-blue-50"]').forEach(el => {
                    if (el.closest('.max-w-7xl')) {
                        el.style.transition = 'opacity 0.5s ease';
                        el.style.opacity = '0';
                        setTimeout(() => el.remove(), 500);
                    }
                });
            }, 5000);

            // Form submission with loading
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('form').forEach(form => {
                    form.addEventListener('submit', function() {
                        // Show loading for forms that don't have data-no-loading attribute
                        if (!this.hasAttribute('data-no-loading')) {
                            CatStoryApp.showLoading();
                        }
                    });
                });
            });
        </script>
    </body>
</html>