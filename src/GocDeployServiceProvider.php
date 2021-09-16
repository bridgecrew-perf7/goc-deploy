<?php

namespace Marcth\GocDeploy;

use Illuminate\Support\ServiceProvider;
use Marcth\GocDeploy\Console\DeployCommand;
use Marcth\GocDeploy\Console\TagCommand;

class GocDeployServiceProvider extends ServiceProvider
{

    /**
     * Register any publishable configuration files to allow users of the package to easily override the default
     * configuration options.
     *
     * @link https://laravel.com/docs/8.x/packages#configuration
     */
    protected function registerPublishableAssets()
    {
        $this->publishes([
            __DIR__ . '/../config/goc-deploy.php' => config_path('goc-deploy.php'),
        ]);
    }

    /**
     * Register the package's console commands to allow package user's access via the Artisan CLI.
     *
     * @link https://laravel.com/docs/8.x/packages#commands
     */
    protected function bootConsoleCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                DeployCommand::class,
            ]);
        }
    }

    /**
     * Register the package application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerPublishableAssets();
    }


    /**
     * Bootstrap the package application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->bootConsoleCommands();
    }
}
