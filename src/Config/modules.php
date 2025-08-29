<?php

declare(strict_types=1);

return [
    // Whether to build and use the modules cache file (bootstrap/cache/modules.php)
    'cache' => true,

    // If true, module service providers are registered lazily upon first call instead of during package boot.
    'lazy' => false,

    // When lazy is enabled, also auto-register middleware & event listeners upon first provider method call.
    'lazy_auto_register' => true,

    // If true, modules whose dependencies are missing will be excluded from cache & registration.
    'strict_dependencies' => true,

    // If true, automatically load model factories from each enabled module's Database/Factories directory.
    'autoload_factories' => true,
];
