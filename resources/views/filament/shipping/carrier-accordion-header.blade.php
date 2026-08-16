@php
    $carrier = $carrier ?? null;
    $expanded = $expanded ?? false;
    $status = $status ?? 'disabled';
    $enabled = $enabled ?? false;

    if (! $carrier) {
        return;
    }

    $statusClass = match ($status) {
        'enabled' => 'is-enabled',
        'incomplete' => 'is-incomplete',
        default => 'is-disabled',
    };

    $statusLabel = match ($status) {
        'enabled' => __('etic.filament.shipping.carrier_status_enabled'),
        'incomplete' => __('etic.filament.shipping.carrier_status_incomplete'),
        default => __('etic.filament.shipping.carrier_status_disabled'),
    };
@endphp

<div
    wire:key="shipping-accordion-{{ $carrier['key'] }}"
    class="etic-shipping-accordion-item {{ $expanded ? 'is-open' : '' }}"
    style="--etic-shipping-accent: {{ $carrier['accent'] }}"
>
    <div class="etic-shipping-accordion-header">
        <button
            type="button"
            class="etic-shipping-accordion-main"
            wire:click="toggleCarrier('{{ $carrier['key'] }}')"
            aria-expanded="{{ $expanded ? 'true' : 'false' }}"
            aria-controls="carrier-panel-{{ $carrier['key'] }}"
        >
            <span class="etic-shipping-accordion-logo">
                <img
                    src="{{ $carrier['logo'] }}"
                    alt="{{ $carrier['name'] }}"
                    loading="lazy"
                >
            </span>

            <span class="etic-shipping-accordion-copy">
                <span class="etic-shipping-accordion-title-row">
                    <span class="etic-shipping-accordion-title">{{ $carrier['name'] }}</span>
                    <span class="etic-shipping-status {{ $statusClass }}">{{ $statusLabel }}</span>
                </span>
                <span class="etic-shipping-accordion-description">
                    {{ \Illuminate\Support\Str::limit($carrier['description'], 140) }}
                </span>
            </span>
        </button>

        <div class="etic-shipping-accordion-actions">
            <button
                type="button"
                class="etic-shipping-toggle {{ $enabled ? 'is-on' : '' }}"
                wire:click="toggleCarrierEnabled('{{ $carrier['key'] }}')"
                title="{{ $enabled ? __('etic.filament.shipping.carrier_disable') : __('etic.filament.shipping.carrier_enable') }}"
                aria-label="{{ $enabled ? __('etic.filament.shipping.carrier_disable') : __('etic.filament.shipping.carrier_enable') }}"
            >
                <span class="etic-shipping-toggle-track">
                    <span class="etic-shipping-toggle-thumb"></span>
                </span>
            </button>

            <button
                type="button"
                class="etic-shipping-chevron-btn {{ $expanded ? 'is-open' : '' }}"
                wire:click="toggleCarrier('{{ $carrier['key'] }}')"
                aria-label="{{ $expanded ? __('etic.filament.shipping.carrier_collapse') : __('etic.filament.shipping.carrier_expand') }}"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.71a.75.75 0 1 1 1.06 1.06l-4.24 4.25a.75.75 0 0 1-1.06 0L5.21 8.29a.75.75 0 0 1 .02-1.08Z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </div>
</div>
