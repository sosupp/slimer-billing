<?php

namespace Sosupp\SlimerBilling;

use Illuminate\Support\ServiceProvider;
use Override;
use Sosupp\SlimerBilling\Services\BillingService;

class SlimerBillingServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/config.php',
            'slimer-billing-config'
        );

        // Register services
        $this->app->singleton(BillingService::class, function ($app) {
            return new BillingService();
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        
        $this->loadViewsFrom(__DIR__ . '/Views', 'slimerbilling');
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'slimerbilling');

        // Publish configurations
        $this->publishes([
            __DIR__ . '/../config/config.php' => config_path('slimerbilling.php'),
        ], 'slimer-billing-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations'),
        ], 'slimer-billing-migrations');

        // Publish views
        $this->publishes([
            __DIR__ . '/../resources/views/' => resource_path('views/vendor/slimerbilling'),
        ], 'slimer-billing-views');

        // Publish assets
        $this->publishes([
            __DIR__ . '/../public/assets' => public_path('vendor/slimerbilling'),
        ], 'slimer-billing-assets');

        // Publish seeds
        $this->publishes([
            __DIR__ . '/../database/seeders/' => database_path('seeders'),
        ], 'slimer-billing-seeders');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                GenerateBillingNumberCommand::class,
            ]);
        }

        // Register middleware if needed
        $router = $this->app->make(Router::class);
        // $router->aliasMiddleware('billing.auth', \YourVendor\SchoolFeeBilling\Middleware\BillingAuth::class);
    }

}