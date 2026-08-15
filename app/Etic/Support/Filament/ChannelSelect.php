<?php

namespace App\Etic\Support\Filament;

use App\Etic\Support\StoreContext;
use Filament\Forms\Components\Select;
use Lunar\Models\Channel;

class ChannelSelect
{
    public static function make(string $name = 'channel_id'): Select
    {
        return Select::make($name)
            ->label(__('etic.filament.stores.channel'))
            ->options(fn () => Channel::query()->orderBy('name')->pluck('name', 'id'))
            ->default(fn () => app(StoreContext::class)->channelId())
            ->required()
            ->searchable()
            ->preload();
    }
}
