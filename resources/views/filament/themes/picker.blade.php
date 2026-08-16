@php
    $active = $active ?? '';
    $storefrontUrl = $storefrontUrl ?? url('/');
    $themes = $themes ?? [];
@endphp

<style>
    .etic-theme-library {
        --etic-preview-scale: .5;
    }

    .etic-theme-library-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #e5e7eb;
    }

    .etic-theme-library-title {
        display: flex;
        align-items: center;
        gap: .5rem;
    }

    .etic-theme-list {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        padding: 1.5rem;
    }

    .etic-theme-card {
        display: grid;
        grid-template-columns: minmax(17rem, 42%) minmax(0, 1fr);
        overflow: hidden;
        border: 1px solid #e5e7eb;
        border-radius: .875rem;
        background: #fff;
        scroll-margin-top: 6rem;
        transition: border-color 160ms ease, box-shadow 160ms ease, transform 160ms ease;
    }

    .etic-theme-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 35px -24px rgb(15 23 42 / .45);
    }

    .etic-theme-card.is-active {
        border-color: rgb(59 130 246 / .72);
        box-shadow: 0 0 0 3px rgb(59 130 246 / .08);
    }

    .etic-theme-card-content {
        display: flex;
        min-width: 0;
        flex-direction: column;
        padding: 1.5rem;
    }

    .etic-theme-card-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
    }

    .etic-theme-card-title {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .625rem;
    }

    .etic-theme-card-description {
        max-width: 42rem;
        margin-top: .875rem;
        color: #4b5563;
        font-size: .875rem;
        line-height: 1.625;
    }

    .etic-theme-card-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .625rem;
        margin-top: auto;
        padding-top: 1.5rem;
    }

    .etic-theme-preview {
        position: relative;
        overflow: hidden;
        aspect-ratio: 16 / 10;
        align-self: stretch;
        border-right: 1px solid #e5e7eb;
        background: #e5e7eb;
    }

    .etic-theme-preview iframe {
        width: calc(100% / var(--etic-preview-scale));
        height: calc(100% / var(--etic-preview-scale));
        border: 0;
        transform: scale(var(--etic-preview-scale));
        transform-origin: top left;
        pointer-events: none;
    }

    .etic-theme-status {
        display: inline-flex;
        align-items: center;
        gap: .375rem;
        padding: .25rem .625rem;
        border-radius: 9999px;
        background: rgb(37 99 235);
        color: #fff;
        font-size: .75rem;
        font-weight: 600;
        line-height: 1rem;
        box-shadow: 0 1px 3px rgb(15 23 42 / .22);
    }

    .etic-theme-customize .filepond--root {
        min-height: 10rem;
        margin-bottom: 0;
    }

    .etic-theme-customize .filepond--panel-root {
        background-color: #f9fafb;
        border: 1px dashed #d1d5db;
        border-radius: .75rem;
    }

    .etic-theme-customize .filepond--drop-label {
        min-height: 10rem;
        color: #6b7280;
    }

    .etic-theme-customize .filepond--drop-label label {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: .55rem;
        font-size: .875rem;
    }

    .etic-theme-customize .filepond--drop-label label::before {
        width: 1.75rem;
        height: 1.75rem;
        content: "";
        background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='%239ca3af'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z'/%3E%3C/svg%3E") center / contain no-repeat;
    }

    .etic-theme-customize .filepond--label-action {
        color: #2563eb;
        font-weight: 600;
        text-decoration: none;
    }

    .etic-theme-customize .filepond--action-abort-item-processing {
        z-index: 20;
        cursor: pointer;
        pointer-events: auto !important;
    }

    .etic-theme-customize .filepond--file-status {
        pointer-events: auto;
    }

    .etic-theme-home-card {
        transition: border-color 160ms ease, box-shadow 160ms ease;
    }

    .etic-theme-home-card:hover {
        border-color: rgb(59 130 246 / .42);
        box-shadow: 0 10px 24px -22px rgb(37 99 235 / .65);
    }

    .dark .etic-theme-customize .filepond--panel-root {
        background-color: rgb(255 255 255 / .04);
        border-color: rgb(255 255 255 / .16);
    }

    .dark .etic-theme-library-header,
    .dark .etic-theme-preview {
        border-color: rgb(255 255 255 / .1);
    }

    .dark .etic-theme-card {
        border-color: rgb(255 255 255 / .1);
        background: rgb(17 24 39);
    }

    .dark .etic-theme-card.is-active {
        border-color: rgb(96 165 250 / .72);
    }

    .dark .etic-theme-card-description {
        color: #9ca3af;
    }

    @media (max-width: 47.99rem) {
        .etic-theme-library-header {
            align-items: flex-start;
            flex-direction: column;
            padding: 1.125rem;
        }

        .etic-theme-list {
            gap: 1rem;
            padding: 1rem;
        }

        .etic-theme-card {
            grid-template-columns: minmax(0, 1fr);
        }

        .etic-theme-preview {
            border-right: 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .etic-theme-card-content {
            padding: 1.125rem;
        }
    }
</style>

<section class="etic-theme-library overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
    <header class="etic-theme-library-header">
        <div>
            <div class="etic-theme-library-title">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                    {{ __('etic.filament.theme.library') }}
                </h2>
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-white/10 dark:text-gray-300">
                    {{ count($themes) }}
                </span>
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('etic.filament.theme.library_help') }}
            </p>
        </div>

        <x-filament::button
            tag="a"
            :href="$storefrontUrl"
            target="_blank"
            rel="noopener noreferrer"
            color="gray"
            icon="heroicon-o-arrow-top-right-on-square"
        >
            {{ __('etic.filament.theme.open_storefront') }}
        </x-filament::button>
    </header>

    <div class="etic-theme-list">
        @foreach ($themes as $theme)
            @php($selected = $active === $theme['handle'])

            <article
                wire:key="theme-card-{{ $theme['handle'] }}"
                class="etic-theme-card {{ $selected ? 'is-active' : '' }}"
            >
                <div class="etic-theme-preview">
                    <iframe
                        src="{{ $theme['preview_url'] }}"
                        title="{{ __('etic.filament.theme.preview_title', ['name' => $theme['title'] ?? $theme['name']]) }}"
                        loading="lazy"
                        tabindex="-1"
                        aria-hidden="true"
                    ></iframe>

                    <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/10 via-transparent to-transparent"></div>

                </div>

                <div class="etic-theme-card-content">
                    <div class="etic-theme-card-top">
                        <div>
                            <div class="etic-theme-card-title">
                                <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                                    {{ $theme['title'] ?? $theme['name'] }}
                                </h3>
                                @if($selected)
                                    <span class="etic-theme-status">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-3 w-3">
                                            <path fill-rule="evenodd" d="M12.416 3.443a.75.75 0 0 1 .081 1.058l-5.25 6.5a.75.75 0 0 1-1.14.016l-2.5-2.75a.75.75 0 1 1 1.114-1.004l1.916 2.107 4.7-5.823a.75.75 0 0 1 1.079-.104Z" clip-rule="evenodd" />
                                        </svg>
                                        {{ __('etic.filament.theme.active_badge') }}
                                    </span>
                                @endif
                            </div>
                            <p class="mt-1 text-xs font-medium uppercase tracking-wide text-gray-400">
                                {{ $theme['author'] ?? 'Etic Ajans' }} · v{{ $theme['version'] }}
                            </p>
                        </div>

                        <x-filament::dropdown placement="bottom-end" teleport>
                            <x-slot name="trigger">
                                <button
                                    type="button"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5 dark:hover:text-gray-200"
                                    aria-label="{{ __('etic.filament.theme.more') }}"
                                >
                                    <x-filament::icon icon="heroicon-o-ellipsis-horizontal" class="h-5 w-5" />
                                </button>
                            </x-slot>

                            <x-filament::dropdown.list>
                                <x-filament::dropdown.list.item
                                    icon="heroicon-o-arrow-uturn-left"
                                    wire:click="resetTheme('{{ $theme['handle'] }}')"
                                    wire:confirm="{{ __('etic.filament.theme.reset_help') }}"
                                >
                                    {{ __('etic.filament.theme.reset') }}
                                </x-filament::dropdown.list.item>
                            </x-filament::dropdown.list>
                        </x-filament::dropdown>
                    </div>

                    <p class="etic-theme-card-description">
                        {{ $theme['description'] }}
                    </p>

                    <div class="etic-theme-card-actions">
                        @if($selected)
                            <x-filament::button
                                color="primary"
                                icon="heroicon-o-adjustments-horizontal"
                                x-on:click.prevent="document.getElementById('etic-theme-customize')?.scrollIntoView({ behavior: 'smooth', block: 'start' })"
                            >
                                {{ __('etic.filament.theme.customize_action') }}
                            </x-filament::button>
                        @else
                            <x-filament::button
                                color="primary"
                                icon="heroicon-o-rocket-launch"
                                wire:click="publishTheme('{{ $theme['handle'] }}')"
                                wire:confirm="{{ __('etic.filament.theme.publish_confirm', ['name' => $theme['title'] ?? $theme['name']]) }}"
                            >
                                {{ __('etic.filament.theme.publish') }}
                            </x-filament::button>
                        @endif

                        <x-filament::button
                            tag="a"
                            :href="$theme['preview_url']"
                            target="_blank"
                            rel="noopener noreferrer"
                            color="gray"
                            outlined
                            icon="heroicon-o-eye"
                        >
                            {{ __('etic.filament.theme.preview_action') }}
                        </x-filament::button>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
</section>
