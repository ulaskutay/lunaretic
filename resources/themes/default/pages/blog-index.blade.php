<x-storefront-layout>
    <h1 class="mb-6 text-2xl font-semibold">Blog</h1>
    <div class="space-y-4">
        @foreach($posts as $post)
            <a href="{{ route('blog.show', $post->slug) }}" class="block rounded-2xl bg-white p-4">
                <h2 class="font-medium">{{ $post->title }}</h2>
                <p class="text-sm text-neutral-600">{{ $post->excerpt }}</p>
            </a>
        @endforeach
    </div>
</x-storefront-layout>
