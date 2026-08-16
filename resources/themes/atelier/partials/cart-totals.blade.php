@php
    $showShipping = $showShipping ?? false;
    $discountValue = (int) ($cart->discountTotal?->value ?? 0);
@endphp

<dl class="space-y-2 text-sm">
    <div class="flex justify-between">
        <dt class="text-neutral-600">{{ __('etic.storefront.totals.subtotal') }}</dt>
        <dd>{{ $cart->subTotal?->formatted() }}</dd>
    </div>
    @if($discountValue > 0)
        <div class="flex justify-between text-emerald-700">
            <dt>{{ __('etic.storefront.totals.discount') }}@if($cart->coupon_code) ({{ $cart->coupon_code }})@endif</dt>
            <dd>− {{ $cart->discountTotal?->formatted() }}</dd>
        </div>
    @endif
    @if($showShipping && $cart->shippingTotal)
        <div class="flex justify-between">
            <dt class="text-neutral-600">{{ __('etic.storefront.totals.shipping') }}</dt>
            <dd>{{ $cart->shippingTotal->formatted() }}</dd>
        </div>
    @endif
    <div class="flex justify-between border-t pt-2 text-base font-medium">
        <dt>{{ __('etic.storefront.totals.total') }}</dt>
        <dd>{{ $cart->total?->formatted() }}</dd>
    </div>
    @if((int) ($cart->taxTotal?->value ?? 0) > 0)
        <p class="pt-1 text-xs text-neutral-500">{{ __('etic.storefront.totals.tax_included_amount', ['amount' => $cart->taxTotal->formatted()]) }}</p>
    @endif
</dl>
