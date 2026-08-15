<?php

namespace Database\Seeders;

use App\Etic\Support\CommerceBootstrap;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $bootstrap = app(CommerceBootstrap::class);
        $bootstrap->catalog();
        $bootstrap->cms();
        $bootstrap->admin();
    }
}
