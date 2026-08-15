@php
    $appliedCode = $cart->coupon_code;
    $discountValue = (int) ($cart->discountTotal?->value ?? 0);
@endphp

<section class="rounded-2xl border border-neutral-200 bg-white p-4">
    <h2 class="text-sm font-medium">{{ __('etic.storefront.coupon.label') }}</h2>

    @if($appliedCode)
        <div class="mt-3 flex items-center justify-between gap-3">
            <div>
                <p class="text-sm">
                    <span class="rounded-full bg-neutral-900 px-3 py-1 text-xs font-medium tracking-wide text-white">{{ $appliedCode }}</span>
                    <span class="ml-2 text-neutral-600">{{ __('etic.storefront.coupon.active') }}</span>
                </p>
                @if($discountValue > 0)
                    <p class="mt-1 text-sm text-emerald-700">− {{ $cart->discountTotal?->formatted() }}</p>
                @else
                    <p class="mt-1 text-sm text-amber-700">{{ __('etic.storefront.coupon.pending') }}</p>
                @endif
            </div>
            <form method="post" action="{{ route('cart.coupon.remove') }}">
                @csrf
                @method('DELETE')
                <button class="text-sm text-red-600 underline">{{ __('etic.storefront.coupon.remove') }}</button>
            </form>
        </div>
    @else
        <form method="post" action="{{ route('cart.coupon') }}" class="mt-3 flex gap-2">
            @csrf
            <label class="sr-only" for="coupon-code">{{ __('etic.storefront.coupon.label') }}</label>
            <input
                id="coupon-code"
                name="code"
                value="{{ old('code') }}"
                placeholder="{{ __('etic.storefront.coupon.placeholder') }}"
                autocomplete="off"
                class="w-full rounded-xl border px-3 py-2"
            >
            <button class="rounded-xl border border-neutral-900 px-4 py-2 text-sm font-medium">{{ __('etic.storefront.coupon.apply') }}</button>
        </form>
    @endif
</section>
