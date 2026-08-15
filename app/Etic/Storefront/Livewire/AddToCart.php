<?php

namespace App\Etic\Storefront\Livewire;

use App\Etic\Integrations\Marketing\TrackingDispatcher;
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
            $event = collect(app(TrackingDispatcher::class)->events())
                ->last(fn ($item) => $item->name === 'add_to_cart');
            if ($event) {
                $this->js('window.eticTrack && window.eticTrack('.json_encode($event->name).', '.json_encode($event->browserPayload()).')');
            }
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
