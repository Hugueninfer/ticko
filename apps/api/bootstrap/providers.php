<?php

declare(strict_types=1);

use App\Providers\AiServiceProvider;
use App\Providers\ApiDocsServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\HorizonServiceProvider;

return [
    AppServiceProvider::class,
    AiServiceProvider::class,
    HorizonServiceProvider::class,
    ApiDocsServiceProvider::class,
];
