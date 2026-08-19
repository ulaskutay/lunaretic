<?php

use App\Etic\Store\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

function secondStore(): Store
{
    return Store::query()->create([
        'handle' => 'second',
        'name' => 'Second Store',
        'primary_domain' => 'second.test',
        'extra_domains' => ['www.second.test'],
        'theme' => 'default',
        'locale' => 'tr',
        'currency' => 'TRY',
        'is_active' => true,
        'is_default' => false,
    ]);
}
