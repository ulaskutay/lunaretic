<?php

use App\Etic\Support\CommerceBootstrap;

it('returns search suggestions for published products', function () {
    app(CommerceBootstrap::class)->catalog();

    $this->getJson(route('search.suggestions', ['q' => 'klasik']))
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'name', 'url', 'image', 'price']]])
        ->assertJson(fn ($json) => $json->has('data.0.name')->etc());
});

it('returns empty suggestions for short queries', function () {
    app(CommerceBootstrap::class)->catalog();

    $this->getJson(route('search.suggestions', ['q' => 'a']))
        ->assertOk()
        ->assertJsonPath('data', []);
});
