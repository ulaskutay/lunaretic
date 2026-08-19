<?php

use App\Etic\Platform\PlatformPanelProvider;
use App\Etic\Support\EticServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    EticServiceProvider::class,
    PlatformPanelProvider::class,
];
