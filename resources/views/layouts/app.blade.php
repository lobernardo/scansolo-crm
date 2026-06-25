<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'ScanSOLO CRM') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-bg-light font-sans antialiased" x-data="{ sidebarOpen: false }">
    <div class="flex min-h-screen">
        {{-- Mobile overlay --}}
        <div
            x-show="sidebarOpen"
            x-transition:enter="transition-opacity ease-linear duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-40 bg-black/60 lg:hidden"
            @click="sidebarOpen = false"
            x-cloak
        ></div>

        {{-- Sidebar --}}
        <aside
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-sidebar shadow-xl transition-transform duration-200 lg:static lg:translate-x-0"
        >
            {{-- Logo / Brand --}}
            <div class="flex items-center gap-3 border-b border-sidebar-border px-5 py-5">
                <div class="flex size-9 items-center justify-center rounded-lg bg-primary">
                    <svg class="size-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold text-white tracking-wide">ScanSOLO</p>
                    <p class="truncate text-xs text-sidebar-text">{{ auth()->user()->tenant->name ?? '' }}</p>
                </div>
            </div>

            {{-- User info --}}
            <div class="px-5 py-4 border-b border-sidebar-border">
                <p class="text-xs text-sidebar-text">Bem-vindo,</p>
                <p class="text-sm font-semibold text-white">{{ auth()->user()->name ?? '' }}</p>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto px-3 py-4">
                {{-- OPERAÇÃO --}}
                <p class="mb-1.5 px-3 text-xs font-semibold uppercase tracking-widest text-sidebar-text opacity-50">Operação</p>
                <div class="space-y-0.5">
                    <a href="{{ route('dashboard.index') }}" wire:navigate
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors {{ request()->routeIs('dashboard.*') ? 'bg-primary/15 text-primary' : 'text-sidebar-text hover:bg-sidebar-hover hover:text-white' }}">
                        <svg class="size-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                        Dashboard
                    </a>
                    <a href="{{ route('leads.index') }}" wire:navigate
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors {{ request()->routeIs('leads.*') ? 'bg-primary/15 text-primary' : 'text-sidebar-text hover:bg-sidebar-hover hover:text-white' }}">
                        <svg class="size-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Leads &amp; Contatos
                    </a>
                    <a href="{{ route('kanban.index') }}" wire:navigate
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors {{ request()->routeIs('kanban.*') ? 'bg-primary/15 text-primary' : 'text-sidebar-text hover:bg-sidebar-hover hover:text-white' }}">
                        <svg class="size-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                        Pipeline
                    </a>
                    <a href="{{ route('agenda.index') }}" wire:navigate
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors {{ request()->routeIs('agenda.*') ? 'bg-primary/15 text-primary' : 'text-sidebar-text hover:bg-sidebar-hover hover:text-white' }}">
                        <svg class="size-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Agenda &amp; Tarefas
                    </a>
                    <a href="{{ route('relatorios.index') }}" wire:navigate
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors {{ request()->routeIs('relatorios.*') ? 'bg-primary/15 text-primary' : 'text-sidebar-text hover:bg-sidebar-hover hover:text-white' }}">
                        <svg class="size-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        Relatórios
                    </a>
                </div>

                {{-- SISTEMA --}}
                <p class="mb-1.5 mt-5 px-3 text-xs font-semibold uppercase tracking-widest text-sidebar-text opacity-50">Sistema</p>
                <div class="space-y-0.5">
                    <a href="{{ route('projetos.index') }}" wire:navigate
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors {{ request()->routeIs('projetos.*') ? 'bg-primary/15 text-primary' : 'text-sidebar-text hover:bg-sidebar-hover hover:text-white' }}">
                        <svg class="size-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                        Projetos
                    </a>
                    <a href="{{ route('settings.index') }}" wire:navigate
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors {{ request()->routeIs('settings.*') ? 'bg-primary/15 text-primary' : 'text-sidebar-text hover:bg-sidebar-hover hover:text-white' }}">
                        <svg class="size-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Configurações
                    </a>
                </div>
            </nav>

            {{-- Bottom section --}}
            <div class="border-t border-sidebar-border p-3">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-sidebar-text transition-colors hover:bg-sidebar-hover hover:text-secondary-red">
                        <svg class="size-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Sair
                    </button>
                </form>
            </div>
        </aside>

        {{-- Main content --}}
        <div class="flex flex-1 flex-col min-w-0">
            {{-- Top bar --}}
            <header class="flex items-center justify-between border-b border-outline bg-bg-white px-6 py-4">
                <div class="flex items-center gap-4">
                    {{-- Hamburger menu (mobile) --}}
                    <button
                        @click="sidebarOpen = !sidebarOpen"
                        class="text-primary-grey hover:text-primary-dark lg:hidden"
                    >
                        <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <h1 class="text-base font-semibold text-primary-dark">{{ $title ?? 'Dashboard' }}</h1>
                </div>
                <div class="text-sm text-primary-grey">{{ auth()->user()->name ?? '' }}</div>
            </header>

            {{-- Flash messages --}}
            <div class="px-6 pt-4">
                @session('success')
                    <div class="mb-4 rounded-lg border border-secondary-green/30 bg-secondary-green/10 px-4 py-3 text-sm text-secondary-green">
                        {{ $value }}
                    </div>
                @endsession
                @session('error')
                    <div class="mb-4 rounded-lg border border-secondary-red/30 bg-secondary-red/10 px-4 py-3 text-sm text-secondary-red">
                        {{ $value }}
                    </div>
                @endsession
            </div>

            {{-- Page content --}}
            <main class="flex-1 overflow-hidden p-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
