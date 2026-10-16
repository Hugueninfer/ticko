<?php

declare(strict_types=1);

use App\Providers\AiServiceProvider;
use App\Providers\ApiDocsServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\TelemetryServiceProvider;

return [
    TelemetryServiceProvider::class,
    AppServiceProvider::class,
    AiServiceProvider::class,
    HorizonServiceProvider::class,
    ApiDocsServiceProvider::class,
];
