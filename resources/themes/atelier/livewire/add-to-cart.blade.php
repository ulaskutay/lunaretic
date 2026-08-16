<div>
    <button wire:click="add" class="etic-btn px-4 py-2">Sepete ekle</button>
    @error('quantity') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
</div>
