<?php

declare(strict_types=1);

namespace Modules\Tests;

use Modules\Support\ModuleManager;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;

class ModuleVersionConstraintTest extends TestCase
{
    public function test_dependency_version_constraint_enforced()
    {
        $basePath = __DIR__ . '/../';
        $files = new Filesystem();
        // Setup fake manifests
        $blogManifest = [
            'name' => 'Blog',
            'version' => '1.0.0',
            'provider' => 'Modules\\Blog\\Providers\\BlogServiceProvider',
        ];
        $shopManifest = [
            'name' => 'Shop',
            'version' => '1.1.0',
            'provider' => 'Modules\\Shop\\Providers\\ShopServiceProvider',
            'dependencies' => ['Blog'],
            'dependency_versions' => ['Blog' => '>=1.1.0'], // Should fail
        ];
        // Write manifests
        $files->put($basePath . 'Modules/Blog/module.json', json_encode($blogManifest));
        $files->put($basePath . 'Modules/Shop/module.json', json_encode($shopManifest));
        // Enable both modules
        $files->put($basePath . 'modules.json', json_encode(['Blog' => true, 'Shop' => true]));
        $manager = new ModuleManager($basePath, $files);
        $this->expectException(InvalidArgumentException::class);
        $manager->validateDependencyVersions();
    }

    public function test_dependency_version_constraint_passes()
    {
        $basePath = __DIR__ . '/../';
        $files = new Filesystem();
        // Setup manifests
        $blogManifest = [
            'name' => 'Blog',
            'version' => '1.2.0',
            'provider' => 'Modules\\Blog\\Providers\\BlogServiceProvider',
        ];
        $shopManifest = [
            'name' => 'Shop',
            'version' => '1.1.0',
            'provider' => 'Modules\\Shop\\Providers\\ShopServiceProvider',
            'dependencies' => ['Blog'],
            'dependency_versions' => ['Blog' => '>=1.1.0'], // Should pass
        ];
        // Write manifests
        $files->put($basePath . 'Modules/Blog/module.json', json_encode($blogManifest));
        $files->put($basePath . 'Modules/Shop/module.json', json_encode($shopManifest));
        // Enable both modules
        $files->put($basePath . 'modules.json', json_encode(['Blog' => true, 'Shop' => true]));
        $manager = new ModuleManager($basePath, $files);
        $manager->validateDependencyVersions(); // Should not throw
        $this->assertTrue(true);
    }
}
