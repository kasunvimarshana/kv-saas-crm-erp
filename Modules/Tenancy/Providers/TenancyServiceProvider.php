<?php

namespace Modules\Tenancy\Providers;

use Illuminate\Support\ServiceProvider;

class TenancyServiceProvider extends ServiceProvider
{
    /**
     * The module namespace.
     *
     * @var string
     */
    protected $moduleNamespace = 'Modules\Tenancy\Http\Controllers';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path('Tenancy', 'Database/Migrations'));
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            module_path('Tenancy', 'Config/config.php') => config_path('tenancy.php'),
        ], 'tenancy-config');

        $this->mergeConfigFrom(
            module_path('Tenancy', 'Config/config.php'), 'tenancy'
        );
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/tenancy');

        $sourcePath = module_path('Tenancy', 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', 'tenancy-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), 'tenancy');
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/tenancy');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'tenancy');
        } else {
            $this->loadTranslationsFrom(module_path('Tenancy', 'Resources/lang'), 'tenancy');
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * Get the publishable view paths.
     */
    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach ($this->app['config']->get('view.paths') as $path) {
            if (is_dir($path.'/modules/tenancy')) {
                $paths[] = $path.'/modules/tenancy';
            }
        }

        return $paths;
    }
}
