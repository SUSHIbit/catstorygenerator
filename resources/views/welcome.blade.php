<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cat Story Generator</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-slate-50">
    <div class="min-h-screen flex flex-col justify-center items-center">
        <div class="text-center">
            <h1 class="text-6xl font-bold text-slate-800 mb-4">🐱</h1>
            <h2 class="text-4xl font-bold text-slate-700 mb-2">Cat Story Generator</h2>
            <p class="text-xl text-slate-600 mb-8">Transform complex documents into simple cat stories</p>
            
            <div class="space-x-4">
                @auth
                    <a href="{{ route('dashboard') }}" class="bg-slate-800 text-white px-6 py-3 rounded-lg hover:bg-slate-700 transition-colors">
                        Go to Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="bg-slate-800 text-white px-6 py-3 rounded-lg hover:bg-slate-700 transition-colors">
                        Login
                    </a>
                    <a href="{{ route('register') }}" class="bg-slate-600 text-white px-6 py-3 rounded-lg hover:bg-slate-500 transition-colors">
                        Register
                    </a>
                @endauth
            </div>
        </div>
    </div>
</body>
</html>