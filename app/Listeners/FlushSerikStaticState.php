<?php

namespace App\Listeners;

use App\Support\ImageAlt;
use Theme\homzen\Supports\TrebPropertyHelper;

/**
 * Clears request-scoped static caches so Octane workers do not leak
 * property/media state across HTTP requests.
 */
class FlushSerikStaticState
{
    public function handle(object $event): void
    {
        ImageAlt::flushStaticState();

        if (class_exists(TrebPropertyHelper::class)) {
            TrebPropertyHelper::flushStaticState();
        }
    }
}
