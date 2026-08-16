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

<x-theme::layout
    :canonical="$canonical"
    :schema-json="$schemaJson"
    :seo-title="$seoTitle"
    :seo-description="$seoDescription"
    :robots="$robots"
    :og-title="$ogTitle"
    :og-description="$ogDescription"
    :og-image="$ogImage"
>
    {{ $slot }}
</x-theme::layout>
