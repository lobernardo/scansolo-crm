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
<body class="min-h-screen bg-bg-light font-sans antialiased">
    <div class="flex min-h-screen">
        {{-- Left: Form area --}}
        <div class="flex w-full flex-col justify-center px-8 py-12 lg:w-1/2 lg:px-20">
            <div class="mx-auto w-full max-w-md">
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

                {{ $slot }}
            </div>
        </div>

        {{-- Right: Brand panel — ScanSOLO CRM --}}
        <div class="hidden lg:flex lg:w-1/2 lg:flex-col lg:items-center lg:justify-center" style="background: linear-gradient(135deg, #1a2535 0%, #212d45 60%, #2a3a5c 100%);">
            <div class="p-12 text-center text-white">
                {{-- Icon --}}
                <div class="mx-auto mb-6 flex size-20 items-center justify-center rounded-2xl bg-white/10 backdrop-blur-sm">
                    <svg class="size-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12 20.25h.008v.008H12v-.008z"/>
                    </svg>
                </div>

                {{-- Brand name --}}
                <h2 class="text-4xl font-bold tracking-tight text-white">ScanSOLO CRM</h2>
                <p class="mt-3 text-base font-medium text-white/80">Gerencie suas vendas com inteligência.</p>

                {{-- Feature bullets --}}
                <div class="mt-10 space-y-3 text-left">
                    @foreach(['Pipeline visual de negócios', 'Gestão completa de leads', 'Relatórios em tempo real'] as $feature)
                        <div class="flex items-center gap-3">
                            <div class="flex size-6 flex-shrink-0 items-center justify-center rounded-full bg-primary/20">
                                <svg class="size-3.5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <span class="text-sm text-white/90">{{ $feature }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @livewireScripts
</body>
</html>
