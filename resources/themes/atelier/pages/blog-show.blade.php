<x-storefront-layout :canonical="$canonical ?? null" :schema-json="$schemaJson ?? null" :seo-title="$post->title" :seo-description="$post->excerpt" :og-image="$post->featuredImageUrl()">
    <article class="prose max-w-2xl">
        @if($post->featuredImageUrl())
            <img src="{{ $post->featuredImageUrl() }}" alt="" class="mb-6 w-full rounded-2xl">
        @endif
        @if($post->category)
            <p class="text-xs uppercase text-neutral-500">{{ $post->category->name }}</p>
        @endif
        <h1>{{ $post->title }}</h1>
        <p class="text-sm text-neutral-500">{{ $post->author }} · {{ $post->published_at?->format('d.m.Y') }}</p>
        {!! $post->content !!}
        @if($post->tags->isNotEmpty())
            <p class="text-sm">{{ $post->tags->pluck('name')->join(', ') }}</p>
        @endif
    </article>
    @if($related->isNotEmpty())
        <section class="mt-10 max-w-2xl">
            <h2 class="mb-3 text-lg font-medium">Benzer yazılar</h2>
            <div class="space-y-2">
                @foreach($related as $item)
                    <a href="{{ route('blog.show', $item->slug) }}" class="block rounded-xl bg-white p-3 text-sm">{{ $item->title }}</a>
                @endforeach
            </div>
        </section>
    @endif
</x-storefront-layout>
