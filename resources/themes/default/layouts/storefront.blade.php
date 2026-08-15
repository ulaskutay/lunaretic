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
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seoTitle ?? config('etic.store.name') }}</title>
    <meta name="description" content="{{ $seoDescription ?? 'Türk pazarına özel e-ticaret.' }}">
    @if(!empty($canonical))
        <link rel="canonical" href="{{ $canonical }}">
    @endif
    <meta name="robots" content="{{ $robots }}">
    <meta property="og:title" content="{{ $ogTitle ?? ($seoTitle ?? config('etic.store.name')) }}">
    <meta property="og:description" content="{{ $ogDescription ?? ($seoDescription ?? '') }}">
    @if(!empty($ogImage))
        <meta property="og:image" content="{{ $ogImage }}">
    @endif
    @if(config('etic.tracking.search_console_verification'))
        <meta name="google-site-verification" content="{{ config('etic.tracking.search_console_verification') }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @include('theme::partials.tracking-head')
</head>
<body class="bg-neutral-50 text-neutral-900 antialiased">
    <header class="border-b bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4">
            <a href="{{ route('home') }}" class="text-lg font-semibold tracking-tight">ETIC BOXER</a>
            <nav class="hidden items-center gap-6 text-sm md:flex">
                <a href="{{ route('catalog') }}">Ürünler</a>
                <a href="{{ route('blog.index') }}">Blog</a>
                <a href="{{ route('page', 'hakkimizda') }}">Hakkımızda</a>
            </nav>
            <div class="flex items-center gap-4 text-sm">
                <form action="{{ route('search') }}" method="get" class="hidden md:block">
                    <input type="search" name="q" placeholder="Ara" class="rounded-full border px-3 py-1.5 text-sm" value="{{ request('q') }}">
                </form>
                @livewire('etic.mini-cart')
                @auth
                    <a href="{{ route('account') }}">Hesabım</a>
                @else
                    <a href="{{ route('login') }}">Giriş</a>
                @endauth
            </div>
        </div>
    </header>
    <main class="mx-auto min-h-[70vh] max-w-6xl px-4 py-8">
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
    <footer class="border-t bg-white">
        <div class="mx-auto flex max-w-6xl flex-wrap justify-between gap-4 px-4 py-8 text-sm text-neutral-600">
            <p>&copy; {{ date('Y') }} Etic Commerce</p>
            <div class="flex gap-4">
                <a href="{{ route('page', 'gizlilik') }}">Gizlilik</a>
                <a href="{{ route('page', 'iade') }}">İade</a>
                <a href="{{ route('page', 'kullanim-kosullari') }}">Koşullar</a>
            </div>
        </div>
    </footer>
    @if(!empty($schemaJson))
        <script type="application/ld+json">{!! $schemaJson !!}</script>
    @endif
    @include('theme::partials.tracking-body')
    @livewireScripts
</body>
</html>
