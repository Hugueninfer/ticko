<?php

declare(strict_types=1);

namespace App\Http\Attributes;

use Attribute;

/**
 * Marks an endpoint that only acts on the authenticated user's own session,
 * token or profile. Authentication is its authorization, so the architecture
 * test does not require a policy check for it.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class SelfService {}
