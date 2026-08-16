@props([
    'logoText' => null,
])

@php
    $logoText ??= theme()->logoText();
    $logo = theme()->logoUrl();
    $announcement = theme_setting('announcement');
    $headerStyle = theme_setting('header_style', 'simple');
    $headerMenu = theme()->menu('header');
    $container = theme()->containerClass();
    $links = $headerMenu?->items ?? collect();
@endphp

@if(filled($announcement))
    <div class="bg-brand px-4 py-2 text-center text-xs text-brand-fg">{{ $announcement }}</div>
@endif

<header class="border-b bg-surface">
    <div @class([
        'mx-auto flex items-center gap-4 px-4 py-4',
        $container,
        'flex-col text-center md:flex-row md:text-left' => $headerStyle === 'stacked',
        'justify-between' => $headerStyle !== 'stacked',
    ])>
        <a href="{{ route('home') }}" class="text-lg font-semibold tracking-tight text-ink">
            @if($logo)
                <img src="{{ $logo }}" alt="{{ $logoText }}" class="h-8 w-auto">
            @else
                {{ $logoText }}
            @endif
        </a>
        <nav class="hidden items-center gap-6 text-sm md:flex">
            @forelse($links as $item)
                <a href="{{ $item->url }}">{{ $item->label }}</a>
            @empty
                <a href="{{ route('catalog') }}">Ürünler</a>
                <a href="{{ route('blog.index') }}">Blog</a>
                <a href="{{ route('page', 'hakkimizda') }}">Hakkımızda</a>
            @endforelse
        </nav>
        <div class="flex items-center gap-4 text-sm">
            <form action="{{ route('search') }}" method="get" class="hidden md:block">
                <input type="search" name="q" placeholder="Ara" class="etic-input rounded-full px-3 py-1.5 text-sm" value="{{ request('q') }}">
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
