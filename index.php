<?php
include "Redis.config.php";
include "RedisS.class.php";
// include "Redis.class.php";

$redisMS = new RedisMS($server);
$redisMS->runCall('set',["test","haha"]);
echo $redisMS->runCall( "get",["test"]);
echo "<hr><pre>";
print_r($redisMS->log);
echo "</pre>";

// var_dump($redisMS->runCall('set',["test","haha"]));
//
// echo "<pre>";
// var_dump([
//     'master' => $redisMS->GetRedisCall('get'),
//     'slaves' => $redisMS->log,
// ]);

?>
