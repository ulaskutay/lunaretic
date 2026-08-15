<?php

namespace App\Etic\Orders\Filament;

use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Lunar\Admin\Support\Extending\ListPageExtension;

class ListOrdersExtension extends ListPageExtension
{
    public function getTabs(array $tabs): array
    {
        $statuses = collect(config('lunar.orders.statuses', []))
            ->filter(fn ($config) => $config['favourite'] ?? false);

        return [
            'all' => Tab::make(__('lunarpanel::order.tabs.all')),
            ...$statuses->mapWithKeys(
                fn (array $config, string $status) => [
                    $status => Tab::make($config['label'])
                        ->modifyQueryUsing(fn (Builder $query) => $query->where('status', $status)),
                ]
            )->all(),
        ];
    }
}
