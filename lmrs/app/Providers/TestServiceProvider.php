<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class TestServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //绑定
        //App\Contracts\Test接口
        //App\Support\Test实现类
        $this->app->singleton(\App\Contracts\Test::class, function ($app) {
          return new \App\Support\Test($app);
        });

    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
