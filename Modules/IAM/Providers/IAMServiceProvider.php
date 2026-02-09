<?php

declare(strict_types=1);

namespace Modules\IAM\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\IAM\Repositories\Contracts\PermissionRepositoryInterface;
use Modules\IAM\Repositories\PermissionRepository;
use Modules\IAM\Repositories\Contracts\GroupRepositoryInterface;
use Modules\IAM\Repositories\GroupRepository;

class IAMServiceProvider extends ServiceProvider
{
    /**
     * Module namespace.
     */
    protected string $moduleName = 'IAM';

    /**
     * Module namespace alias.
     */
    protected string $moduleNameLower = 'iam';

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        
        // Register config
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'),
            $this->moduleNameLower
        );

        // Register repositories
        $this->app->bind(
            PermissionRepositoryInterface::class,
            PermissionRepository::class
        );

        $this->app->bind(
            GroupRepositoryInterface::class,
            GroupRepository::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerMigrations();
        $this->publishConfig();
    }

    /**
     * Register module migrations.
     */
    protected function registerMigrations(): void
    {
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
    }

    /**
     * Publish module config.
     */
    protected function publishConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }
}
