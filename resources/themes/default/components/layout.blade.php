@props([
    'canonical' => null,
    'schemaJson' => null,
    'seoTitle' => null,
    'seoDescription' => null,
    'robots' => 'index,follow',
    'ogTitle' => null,
    'ogDescription' => null,
    'ogImage' => null,
])

@php
    $logoText = theme()->logoText();
    $favicon = theme()->faviconHref();
@endphp

<!DOCTYPE html>
<html lang="{{ $eticStore->locale() }}" style="{{ theme()->cssVariablesStyle() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seoTitle ?? $eticStore->name() }}</title>
    <meta name="description" content="{{ $seoDescription ?? 'Türk pazarına özel e-ticaret.' }}">
    <link rel="icon" href="{{ $favicon }}">
    <link rel="apple-touch-icon" href="{{ $favicon }}">
    @if(!empty($canonical))
        <link rel="canonical" href="{{ $canonical }}">
    @endif
    <meta name="robots" content="{{ $robots }}">
    <meta property="og:title" content="{{ $ogTitle ?? ($seoTitle ?? $eticStore->name()) }}">
    <meta property="og:description" content="{{ $ogDescription ?? ($seoDescription ?? '') }}">
    @if(!empty($ogImage))
        <meta property="og:image" content="{{ $ogImage }}">
    @endif
    @vite(theme()->viteInputs())
    @livewireStyles
    @include('theme::partials.tracking-head')
</head>
<body class="bg-canvas font-sans text-ink antialiased">
    <x-theme::header :logo-text="$logoText" />
    <main class="etic-main mx-auto min-h-[70vh] {{ theme()->containerClass() }} px-4 py-8">
        @if(session('status'))
            <p class="mb-4 rounded bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ session('status') }}</p>
        @endif
        @if($errors->any())
            <ul class="mb-4 rounded bg-red-50 px-3 py-2 text-sm text-red-800">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
        {{ $slot }}
    </main>
    <x-theme::footer />
    @if(!empty($schemaJson))
        <script type="application/ld+json">{!! $schemaJson !!}</script>
    @endif
    @include('theme::partials.tracking-body')
    @livewireScripts
</body>
</html>
