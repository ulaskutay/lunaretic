<?php

namespace App\Etic\Storefront\Livewire;

use App\Etic\Storefront\CartManager;
use Livewire\Component;
use RuntimeException;

class AddToCart extends Component
{
    public int $variantId;

    public int $quantity = 1;

    public function add(CartManager $carts): void
    {
        try {
            $carts->add($this->variantId, $this->quantity);
            $this->dispatch('cart-updated');
        } catch (RuntimeException $e) {
            $this->addError('quantity', $e->getMessage());
        }
    }

    public function render()
    {
        return view('theme::livewire.add-to-cart');
    }
}
