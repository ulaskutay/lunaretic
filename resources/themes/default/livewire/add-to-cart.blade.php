<div>
    <button wire:click="add" class="rounded-full bg-neutral-900 px-4 py-2 text-sm text-white">Sepete ekle</button>
    @error('quantity') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
</div>
