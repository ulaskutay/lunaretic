<x-storefront-layout
    :canonical="$canonical ?? null"
    :schema-json="$schemaJson ?? null"
    :seo-title="$page->seo->title ?? $page->title"
    :seo-description="$page->seo->description ?? ($cms['lead'] ?? '')"
    :robots="$page->seo->robots ?? 'index,follow'"
    :og-title="$page->seo->og_title ?? null"
    :og-description="$page->seo->og_description ?? null"
    :og-image="$page->seo->og_image ?? ($cms['image'] ?? null)"
>
    @php
        $cms = $cms ?? [];
        $template = $cms['template'] ?? 'page';
        $related = collect($cms['related'] ?? []);
        $faq = collect($cms['faq'] ?? []);
        $highlights = collect($cms['highlights'] ?? []);
        $contacts = collect($cms['contacts'] ?? []);
        $cta = $cms['cta'] ?? ['label' => 'Koleksiyonu keşfet', 'url' => route('catalog')];
        $ctaUrl = str_starts_with((string) ($cta['url'] ?? ''), 'http') ? $cta['url'] : url($cta['url'] ?? '/koleksiyon');
        $body = $cms['body'] ?? $page->content;
        $showSidebar = in_array($template, ['legal', 'faq', 'contact'], true) && $related->isNotEmpty();
    @endphp

    <article class="etic-static etic-static--{{ $template }}">
        <header class="etic-static__header">
            <nav class="etic-static__crumb" aria-label="Sayfa konumu">
                <a href="{{ route('home') }}">{{ __('etic.storefront.pages.home') }}</a>
                <span aria-hidden="true">/</span>
                <span>{{ $page->title }}</span>
            </nav>
            <p class="etic-static__kicker">{{ $cms['kicker'] ?? 'Bilgi' }}</p>
            <h1>{{ $page->title }}</h1>
            @if($template !== 'story' && filled($cms['lead'] ?? null))
                <p class="etic-static__lede">{{ $cms['lead'] }}</p>
            @endif
            @if($template === 'legal' && filled($cms['updated_at'] ?? null))
                <p class="etic-static__meta">{{ __('etic.storefront.pages.updated') }}: {{ $cms['updated_at'] }}</p>
            @endif
        </header>

        @if($template === 'story')
            <section class="etic-static__intro">
                <div class="etic-static__copy">
                    @if(filled($cms['lead'] ?? null))
                        <p class="etic-static__lede">{{ $cms['lead'] }}</p>
                    @endif
                    <a class="etic-static__link" href="{{ $ctaUrl }}">{{ $cta['label'] }}</a>
                </div>
                <div class="etic-static__media">
                    @if(filled($cms['image'] ?? null))
                        <img src="{{ $cms['image'] }}" alt="" loading="lazy" decoding="async">
                    @else
                        <div class="etic-static__portrait" aria-hidden="true">
                            <span>{{ $cms['brand'] ?? theme()->logoText() }}</span>
                        </div>
                    @endif
                </div>
            </section>

            @if($highlights->isNotEmpty())
                <section class="etic-static__highlights" aria-label="Alışveriş avantajları">
                    @foreach($highlights as $index => $item)
                        <article>
                            <span>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                            <h2>{{ $item['title'] }}</h2>
                            <p>{{ $item['description'] }}</p>
                        </article>
                    @endforeach
                </section>
            @endif
        @endif

        @if($template === 'contact' && $contacts->isNotEmpty())
            <section class="etic-static__contacts" aria-label="İletişim kanalları">
                @foreach($contacts as $item)
                    @if(filled($item['href'] ?? null))
                        <a class="etic-static__card" href="{{ $item['href'] }}" @if(str_starts_with((string) $item['href'], 'http')) rel="noopener noreferrer" target="_blank" @endif>
                            <p>{{ $item['label'] }}</p>
                            <strong>{{ $item['value'] }}</strong>
                            <span>{{ $item['hint'] }}</span>
                        </a>
                    @else
                        <div class="etic-static__card">
                            <p>{{ $item['label'] }}</p>
                            <strong>{{ $item['value'] }}</strong>
                            <span>{{ $item['hint'] }}</span>
                        </div>
                    @endif
                @endforeach
            </section>
        @endif

        <div @class(['etic-static__layout' => $showSidebar])>
            @if($showSidebar)
                <aside class="etic-static__nav" aria-label="{{ __('etic.storefront.pages.help') }}">
                    <p>{{ __('etic.storefront.pages.help') }}</p>
                    @foreach($related as $item)
                        <a href="{{ url($item['url']) }}" @class(['is-active' => ! empty($item['current'])])>{{ $item['title'] }}</a>
                    @endforeach
                </aside>
            @endif

            <div class="etic-static__main">
                @if($template === 'faq' && $faq->isNotEmpty())
                    <div class="etic-static__faq">
                        @foreach($faq as $index => $item)
                            <details @if($index === 0) open @endif>
                                <summary>{{ $item['question'] }}</summary>
                                <div class="etic-static__prose">{!! $item['answer'] !!}</div>
                            </details>
                        @endforeach
                    </div>
                @elseif(filled($body))
                    <div class="etic-static__prose">{!! $body !!}</div>
                @elseif(filled($page->content) && $template !== 'story')
                    <div class="etic-static__prose">{!! $page->content !!}</div>
                @endif
            </div>
        </div>

        @if($template === 'story')
            <section class="etic-static__cta">
                <p>Seçkimizi keşfedin.</p>
                <a href="{{ $ctaUrl }}">{{ $cta['label'] }}</a>
            </section>
        @endif
    </article>
</x-storefront-layout>
