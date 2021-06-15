<?php
/**
 * author:六星教育-星空老师
 */

namespace App\pool;

use Swoole\Runtime;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;

class Redis
{
    public static function getDriver()
    {
        return app(app('config')->get("database.redis.default.driver"));
    }

    public static function __callStatic($name, $arguments)
    {
        Runtime::enableCoroutine();
        $chan = new Channel(3);
        go(function () use ($chan,$name,$arguments){
            global $result;
            //$redis = app('redis');
            $redis = self::getDriver();
            $return = $redis->{$name}(...$arguments);
            $chan->push("123");
            // $chan->push($return);
            $result = $chan->pop();
        });


        // return $result;
        return $result;
    }
}
