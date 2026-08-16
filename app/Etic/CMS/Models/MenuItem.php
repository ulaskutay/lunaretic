<?php

namespace App\Etic\CMS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    protected $table = 'etic_menu_items';

    protected $fillable = ['menu_id', 'parent_id', 'label', 'url', 'position'];

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    protected static function booted(): void
    {
        static::saving(function (MenuItem $item): void {
            if (filled($item->menu_id) || blank($item->parent_id)) {
                return;
            }

            $item->menu_id = static::query()->whereKey($item->parent_id)->value('menu_id');
        });
    }
}
