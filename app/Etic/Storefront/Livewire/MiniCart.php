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

        return view('theme::livewire.mini-cart', [
            'count' => $cart->lines->sum('quantity'),
            'total' => $cart->total?->formatted() ?? '0 ₺',
        ]);
    }
}
