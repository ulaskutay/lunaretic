<?php

use App\Etic\Catalog\AssignProductAvailability;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(AssignProductAvailability::class)->backfill();
    }

    public function down(): void
    {
        //
    }
};
