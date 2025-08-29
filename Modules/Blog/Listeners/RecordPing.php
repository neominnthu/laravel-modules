<?php

declare(strict_types=1);

namespace Modules\Blog\Listeners;

use Modules\Blog\Events\Pinged;

class RecordPing
{
    public static array $records = [];

    public function handle(Pinged $event): void
    {
        self::$records[] = $event->origin;
    }
}
