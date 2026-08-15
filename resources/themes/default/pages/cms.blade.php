<x-storefront-layout :canonical="$canonical ?? null" :seo-title="$page->seo->title ?? $page->title" :seo-description="$page->seo->description ?? ''" :robots="$page->seo->robots ?? 'index,follow'">
    <article class="prose max-w-2xl">
        <h1>{{ $page->title }}</h1>
        {!! $page->content !!}
    </article>
</x-storefront-layout>
