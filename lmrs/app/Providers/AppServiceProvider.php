<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Elasticsearch\ClientBuilder as ESClientBuilder;
use App\pool\Core\CoRedis;
use App\pool\Redis\RedisPool;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('es',function(){
            $builder = ESClientBuilder::create()->setHosts(config('database.elasticsearch.hosts'));
            if(app()->environment() === 'local'){
                $builder->setLogger(app('log')->driver());
            }
            return $builder->build();
        });

        $this->app->singleton('redis',function (){
            return new CoRedis(new RedisPool($this->app));
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
