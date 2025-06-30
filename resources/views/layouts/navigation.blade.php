<nav x-data="{ open: false }" class="gradient-bg shadow-lg border-b border-slate-800">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center group">
                        <span class="text-3xl mr-3 float-animation">🐱</span>
                        <div>
                            <h1 class="text-xl font-bold text-white group-hover:text-slate-200 transition-colors">
                                Cat Story Generator
                            </h1>
                            <p class="text-xs text-slate-400 hidden sm:block">
                                Transform docs into cat stories
                            </p>
                        </div>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" 
                                class="text-slate-300 hover:text-white border-slate-600 hover:border-slate-400">
                        <span class="flex items-center">
                            <span class="mr-2">🏠</span>
                            {{ __('Dashboard') }}
                        </span>
                    </x-nav-link>
                    
                    <x-nav-link :href="route('documents.index')" :active="request()->routeIs('documents.*')"
                                class="text-slate-300 hover:text-white border-slate-600 hover:border-slate-400">
                        <span class="flex items-center">
                            <span class="mr-2">📚</span>
                            {{ __('Documents') }}
                        </span>
                    </x-nav-link>
                </div>
            </div>

            <!-- Right Side Items -->
            <div class="hidden sm:flex sm:items-center sm:space-x-4">
                <!-- Quick Stats -->
                @php
                    $userStats = [
                        'total' => auth()->user()->documents()->count(),
                        'completed' => auth()->user()->documents()->completed()->count(),
                    ];
                @endphp
                
                @if($userStats['total'] > 0)
                    <div class="flex items-center space-x-4 text-sm text-slate-300">
                        <div class="flex items-center">
                            <span class="mr-1">📚</span>
                            <span>{{ $userStats['total'] }}</span>
                        </div>
                        <div class="flex items-center">
                            <span class="mr-1">🐱</span>
                            <span>{{ $userStats['completed'] }}</span>
                        </div>
                    </div>
                    <div class="w-px h-6 bg-slate-600"></div>
                @endif

                <!-- Upload Button -->
                <a href="{{ route('documents.create') }}" 
                   class="bg-slate-600 hover:bg-slate-500 text-white px-4 py-2 rounded-lg transition-colors text-sm font-medium flex items-center">
                    <span class="mr-2">📤</span>
                    Upload
                </a>

                <!-- Settings Dropdown -->
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-lg text-slate-300 bg-slate-700 hover:bg-slate-600 hover:text-white focus:outline-none focus:bg-slate-600 transition ease-in-out duration-150">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-slate-500 rounded-full flex items-center justify-center mr-2">
                                    <span class="text-white text-sm font-medium">
                                        {{ substr(Auth::user()->name, 0, 1) }}
                                    </span>
                                </div>
                                <div class="hidden md:block text-left">
                                    <div class="text-sm">{{ Auth::user()->name }}</div>
                                </div>
                            </div>

                            <div class="ml-2">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-3 border-b border-slate-200">
                            <p class="text-sm text-slate-700 font-medium">{{ Auth::user()->name }}</p>
                            <p class="text-xs text-slate-500">{{ Auth::user()->email }}</p>
                        </div>
                        
                        <x-dropdown-link :href="route('profile.edit')" class="flex items-center">
                            <span class="mr-3">👤</span>
                            {{ __('Profile Settings') }}
                        </x-dropdown-link>

                        <x-dropdown-link :href="route('documents.index')" class="flex items-center">
                            <span class="mr-3">📁</span>
                            {{ __('My Documents') }}
                        </x-dropdown-link>

                        <div class="border-t border-slate-200"></div>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();"
                                    class="flex items-center text-red-600 hover:bg-red-50">
                                <span class="mr-3">🚪</span>
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-mr-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-slate-400 hover:text-slate-300 hover:bg-slate-700 focus:outline-none focus:bg-slate-700 focus:text-slate-300 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-t border-slate-700">
        <div class="pt-2 pb-3 space-y-1 bg-slate-800">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')"
                                   class="text-slate-300 hover:text-white hover:bg-slate-700 border-slate-600">
                <span class="flex items-center">
                    <span class="mr-3">🏠</span>
                    {{ __('Dashboard') }}
                </span>
            </x-responsive-nav-link>
            
            <x-responsive-nav-link :href="route('documents.index')" :active="request()->routeIs('documents.*')"
                                   class="text-slate-300 hover:text-white hover:bg-slate-700 border-slate-600">
                <span class="flex items-center">
                    <span class="mr-3">📚</span>
                    {{ __('Documents') }}
                </span>
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('documents.create')"
                                   class="text-slate-300 hover:text-white hover:bg-slate-700 border-slate-600">
                <span class="flex items-center">
                    <span class="mr-3">📤</span>
                    {{ __('Upload Document') }}
                </span>
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-slate-700 bg-slate-800">
            <div class="px-4 mb-3">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-slate-500 rounded-full flex items-center justify-center mr-3">
                        <span class="text-white font-medium">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </span>
                    </div>
                    <div>
                        <div class="font-medium text-base text-slate-200">{{ Auth::user()->name }}</div>
                        <div class="font-medium text-sm text-slate-400">{{ Auth::user()->email }}</div>
                    </div>
                </div>
                
                @if($userStats['total'] > 0)
                    <div class="mt-3 flex items-center space-x-4 text-sm text-slate-400">
                        <div class="flex items-center">
                            <span class="mr-1">📚</span>
                            <span>{{ $userStats['total'] }} docs</span>
                        </div>
                        <div class="flex items-center">
                            <span class="mr-1">🐱</span>
                            <span>{{ $userStats['completed'] }} stories</span>
                        </div>
                    </div>
                @endif
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')"
                                       class="text-slate-300 hover:text-white hover:bg-slate-700">
                    <span class="flex items-center">
                        <span class="mr-3">👤</span>
                        {{ __('Profile') }}
                    </span>
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();"
                            class="text-red-400 hover:text-red-300 hover:bg-slate-700">
                        <span class="flex items-center">
                            <span class="mr-3">🚪</span>
                            {{ __('Log Out') }}
                        </span>
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>

<style>
    .gradient-bg {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    }
    
    .float-animation {
        animation: float 3s ease-in-out infinite;
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }
</style>