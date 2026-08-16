<x-storefront-layout :seo-title="'Blog | '.$eticStore->name()" seo-description="Boxer bakımı, kumaş ve beden rehberleri.">
    <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <h1 class="text-2xl font-semibold">Blog</h1>
        @if($categories->isNotEmpty())
            <nav class="flex flex-wrap gap-2 text-sm">
                <a href="{{ route('blog.index') }}" class="rounded-full px-3 py-1 {{ empty($currentCategory) ? 'bg-neutral-900 text-white' : 'bg-white' }}">Tümü</a>
                @foreach($categories as $category)
                    <a href="{{ route('blog.index', ['kategori' => $category->slug]) }}" class="rounded-full px-3 py-1 {{ ($currentCategory ?? null) === $category->slug ? 'bg-neutral-900 text-white' : 'bg-white' }}">{{ $category->name }}</a>
                @endforeach
            </nav>
        @endif
    </div>
    <div class="space-y-4">
        @forelse($posts as $post)
            <a href="{{ route('blog.show', $post->slug) }}" class="block rounded-2xl bg-white p-4">
                @if($post->featuredImageUrl())
                    <img src="{{ $post->featuredImageUrl() }}" alt="" class="mb-3 h-40 w-full rounded-xl object-cover">
                @endif
                @if($post->category)
                    <p class="text-xs uppercase text-neutral-500">{{ $post->category->name }}</p>
                @endif
                <h2 class="font-medium">{{ $post->title }}</h2>
                <p class="text-sm text-neutral-600">{{ $post->excerpt }}</p>
            </a>
        @empty
            <p class="text-sm text-neutral-600">Henüz yazı yok.</p>
        @endforelse
    </div>
    <div class="mt-8">{{ $posts->links() }}</div>
</x-storefront-layout>
