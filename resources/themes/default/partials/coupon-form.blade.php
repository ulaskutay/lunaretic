@php
    $appliedCode = $cart->coupon_code;
    $discountValue = (int) ($cart->discountTotal?->value ?? 0);
    $embedded = $embedded ?? false;
    $formTarget = $formTarget ?? 'inline';
    $applyFormId = $applyFormId ?? 'etic-cart-coupon-apply';
    $removeFormId = $removeFormId ?? 'etic-cart-coupon-remove';
    $inputId = $inputId ?? 'coupon-code';
@endphp

<section @class(['etic-cart__coupon' => $embedded, 'rounded-2xl border border-neutral-200 bg-white p-4' => ! $embedded])>
    @if(! $embedded)
        <h2 class="text-sm font-medium">{{ __('etic.storefront.coupon.label') }}</h2>
    @endif

    @if($appliedCode)
        <div @class(['etic-cart__coupon-applied' => $embedded, 'mt-3 flex items-center justify-between gap-3' => ! $embedded])>
            <div>
                @if($embedded)
                    <p><strong>{{ $appliedCode }}</strong> — {{ __('etic.storefront.coupon.active') }}</p>
                @else
                    <p class="text-sm">
                        <span class="rounded-full bg-neutral-900 px-3 py-1 text-xs font-medium tracking-wide text-white">{{ $appliedCode }}</span>
                        <span class="ml-2 text-neutral-600">{{ __('etic.storefront.coupon.active') }}</span>
                    </p>
                @endif
                @if($discountValue > 0)
                    <p @class(['mt-1 text-sm text-emerald-700' => ! $embedded])>− {{ $cart->discountTotal?->formatted() }}</p>
                @else
                    <p class="mt-1 text-sm text-amber-700">{{ __('etic.storefront.coupon.pending') }}</p>
                @endif
            </div>
            @if($formTarget === 'external')
                <button type="submit" form="{{ $removeFormId }}" @class(['text-sm text-red-600 underline' => ! $embedded])>{{ __('etic.storefront.coupon.remove') }}</button>
            @else
                <form method="post" action="{{ route('cart.coupon.remove') }}">
                    @csrf
                    @method('DELETE')
                    <button @class(['text-sm text-red-600 underline' => ! $embedded])>{{ __('etic.storefront.coupon.remove') }}</button>
                </form>
            @endif
        </div>
    @else
        @if($formTarget === 'external')
            <div @class(['etic-cart__coupon-form' => $embedded, 'mt-3 flex gap-2' => ! $embedded])>
                <label class="sr-only" for="{{ $inputId }}">{{ __('etic.storefront.coupon.label') }}</label>
                <input
                    id="{{ $inputId }}"
                    form="{{ $applyFormId }}"
                    name="code"
                    value="{{ old('code') }}"
                    placeholder="{{ $embedded ? 'İndirim kodu' : __('etic.storefront.coupon.placeholder') }}"
                    autocomplete="off"
                    @class(['w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm' => ! $embedded])
                >
                <button type="submit" form="{{ $applyFormId }}" @class(['rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-brand-fg' => ! $embedded])>{{ __('etic.storefront.coupon.apply') }}</button>
            </div>
        @else
            <form method="post" action="{{ route('cart.coupon') }}" @class(['etic-cart__coupon-form' => $embedded, 'mt-3 flex gap-2' => ! $embedded])>
                @csrf
                <label class="sr-only" for="{{ $inputId }}">{{ __('etic.storefront.coupon.label') }}</label>
                <input
                    id="{{ $inputId }}"
                    name="code"
                    value="{{ old('code') }}"
                    placeholder="{{ $embedded ? 'İndirim kodu' : __('etic.storefront.coupon.placeholder') }}"
                    autocomplete="off"
                    @class(['w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm' => ! $embedded])
                >
                <button type="submit" @class(['rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-brand-fg' => ! $embedded])>{{ __('etic.storefront.coupon.apply') }}</button>
            </form>
        @endif
    @endif
</section>
