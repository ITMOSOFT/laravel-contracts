<?php

namespace ITMOSOFT\Contracts;

use Illuminate\Support\ServiceProvider;

class ContractsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/contracts.php', 'itmosoft-contracts');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/contracts.php' => config_path('contracts.php'),
        ], 'contracts-config');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
