<x-storefront-layout :canonical="$canonical ?? null" :schema-json="$schemaJson ?? null" :seo-title="$post->title">
    <article class="prose max-w-2xl">
        <h1>{{ $post->title }}</h1>
        <p class="text-sm text-neutral-500">{{ $post->author }} · {{ $post->published_at?->format('d.m.Y') }}</p>
        {!! $post->content !!}
    </article>
</x-storefront-layout>
