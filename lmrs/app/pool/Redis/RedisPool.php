<?php
/**
 * author:六星教育-星空老师
 */

namespace App\pool\Redis;

use Swoole\Database\RedisPool as Pool;
use Swoole\Database\RedisConfig;
use Illuminate\Foundation\Application;

class RedisPool
{
    /**
     * @var Application
     * author:六星教育-星空老师
     * laravel的应用
     */
    protected $app;

    /*
     *获取Redis的配置
     */
    protected $config;

    /**
     * @var
     * author:六星教育-星空老师
     * 池
     */
    protected $pool;

    public function  __construct(Application $app)
    {
        $this->app = $app;
        $this->config = $app->make("config");
        $this->init();
    }

    public function init()
    {
        $config = (new RedisConfig())
            ->withHost($this->config->get('database.redis.default.host'))
            ->withPort($this->config->get('database.redis.default.port'))
            ->withAuth('')
            ->withTimeout(3);
            //$this->config->get('database.redis.default.password')
        $this->pool = new Pool($config,$this->config->get('database.redis.default.pool.size'));
    }

    public function get()
    {
        return $this->pool->get();
    }

    public function put($redis)
    {
        return $this->pool->put($redis);
    }

}
