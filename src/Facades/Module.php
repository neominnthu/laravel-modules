<?php

declare(strict_types=1);

namespace Modules\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Support\ModuleManager;

/**
 * @method static bool enabled(string $name)
 * @method static void enable(string $name)
 * @method static void disable(string $name)
 * @method static string|null path(string $name)
 * @method static string|null version(string $name)
 * @method static array manifest(string $name)
 * @method static array cached()
 * @method static mixed call(string $target, array $parameters = [])
 *
 * @see ModuleManager
 */
class Module extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'modules.manager';
    }
}
