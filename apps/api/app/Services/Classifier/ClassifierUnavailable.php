<?php

declare(strict_types=1);

namespace App\Services\Classifier;

use RuntimeException;

final class ClassifierUnavailable extends RuntimeException
{
    public function __construct(public readonly string $errorCode)
    {
        parent::__construct($errorCode);
    }
}
