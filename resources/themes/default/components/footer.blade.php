@php
    $footerMenu = theme()->menu('footer');
    $links = $footerMenu?->items ?? collect();
    $social = [
        'Instagram' => theme_setting('social_instagram'),
        'TikTok' => theme_setting('social_tiktok'),
        'Facebook' => theme_setting('social_facebook'),
    ];
    $whatsapp = theme_setting('social_whatsapp');
    $footerText = theme_setting('footer_text');
@endphp

<footer class="border-t bg-surface text-muted">
    <div class="mx-auto flex {{ theme()->containerClass() }} flex-wrap justify-between gap-4 px-4 py-8 text-sm">
        <div>
            <p>&copy; {{ date('Y') }} {{ theme()->logoText() }}</p>
            @if(filled($footerText))
                <p class="mt-2 max-w-md">{{ $footerText }}</p>
            @endif
        </div>
        <div class="flex flex-wrap gap-4">
            @forelse($links as $item)
                <a href="{{ $item->url }}">{{ $item->label }}</a>
            @empty
                <a href="{{ route('page', 'gizlilik') }}">Gizlilik</a>
                <a href="{{ route('page', 'iade') }}">İade</a>
                <a href="{{ route('page', 'kullanim-kosullari') }}">Koşullar</a>
            @endforelse
        </div>
        <div class="flex flex-wrap gap-4">
            @foreach($social as $label => $url)
                @if(filled($url))
                    <a href="{{ $url }}" rel="noopener noreferrer" target="_blank">{{ $label }}</a>
                @endif
            @endforeach
            @if(filled($whatsapp))
                <a href="https://wa.me/{{ preg_replace('/\D+/', '', $whatsapp) }}" rel="noopener noreferrer" target="_blank">WhatsApp</a>
            @endif
        </div>
    </div>
</footer>
