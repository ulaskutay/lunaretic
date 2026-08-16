<a href="{{ route('cart.show') }}" class="etic-icon-btn" data-etic-cart-target aria-label="Sepet{{ $count ? ' ('.$count.')' : '' }}">
    <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M7.5 8.5V7.2A4.5 4.5 0 0 1 12 2.8a4.5 4.5 0 0 1 4.5 4.4v1.3" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
        <path d="M6.4 8.5h11.2l.7 11.2H5.7Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
    </svg>
    @if($count > 0)
        <span class="etic-icon-btn__badge">{{ $count > 99 ? '99+' : $count }}</span>
    @endif
</a>
