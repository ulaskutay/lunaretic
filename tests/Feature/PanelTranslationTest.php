<?php

it('translates product list filter tabs in turkish', function () {
    expect(__('lunarpanel::product.tabs.all'))->toBe('Tümü')
        ->and(__('lunarpanel::product.tabs.published'))->toBe('Yayında')
        ->and(__('lunarpanel::product.tabs.draft'))->toBe('Taslak');
});

it('translates the save variants button in turkish', function () {
    expect(__('lunarpanel::productoption.widgets.product-options.actions.save-variants.label'))
        ->toBe('Varyantları kaydet');
});
