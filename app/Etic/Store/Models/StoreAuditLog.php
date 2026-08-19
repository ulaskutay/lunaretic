<?php

namespace App\Etic\Store\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Admin\Models\Staff;

class StoreAuditLog extends Model
{
    protected $table = 'etic_store_audit_logs';

    protected $fillable = [
        'store_id',
        'staff_id',
        'action',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }
}
