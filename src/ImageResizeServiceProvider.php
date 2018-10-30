<?php

namespace Mxmm\ImageResize;

use Illuminate\Support\ServiceProvider;

class ImageResizeServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config.php' => config_path('image-resize.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/config.php', 'image-resize'
        );

        $this->app->singleton('imageResize', function ($app) {
            return new ImageResize($app->config->get('image-resize', []));
        });
    }
}
