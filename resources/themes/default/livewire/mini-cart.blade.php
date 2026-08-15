<div>
    <p class="text-sm">Sepet: {{ $count }} · {{ $total }}</p>
    @if($discount)
        <p class="text-xs text-emerald-700">İndirim: − {{ $discount }}</p>
    @endif
    <a href="{{ route('cart.show') }}" class="underline">Sepete git</a>
</div>
