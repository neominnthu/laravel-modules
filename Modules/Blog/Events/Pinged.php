<?php

declare(strict_types=1);

namespace Modules\Blog\Events;

class Pinged
{
    public function __construct(public string $origin)
    {
    }
}
