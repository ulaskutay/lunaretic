<?php

namespace App\Etic\Storefront\Livewire;

use App\Etic\Storefront\CartManager;
use Livewire\Attributes\On;
use Livewire\Component;

class MiniCart extends Component
{
    #[On('cart-updated')]
    public function refresh(): void {}

    public function render(CartManager $carts)
    {
        $cart = $carts->current()->calculate();

        $discountValue = (int) ($cart->discountTotal?->value ?? 0);

        return view('theme::livewire.mini-cart', [
            'count' => $cart->lines->sum('quantity'),
            'total' => $cart->total?->formatted() ?? '0 ₺',
            'discount' => $discountValue > 0 ? $cart->discountTotal?->formatted() : null,
        ]);
    }
}
