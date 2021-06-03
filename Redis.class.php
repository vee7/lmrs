<?php
class RedisMS
{
	protected $server;
	protected $conn;
	protected $log;
	protected $call = [
		"write"=>[
			"set",
			"hset",
			"sadd",
			"lpush",
			"lpop",
			"rpush",
			"rpop",
			"incr",
			"decr"
		],
		"read"=>[
			"get",
			"hget",
			"hgetall",
			"smembers",
			"llen"
		]
	];

	public function __construct($server)
	{
		$this->server = $server;
		if($server["is_open"]!=True){
			$this->conn["master"] = "配置未开启";
			$this->conn["slave"] = "配置未开启";
		}
		 
	}

	public function ConnMaster()
	{
		return $this->conn["master"];
	}

	public function ConnSlave()
	{
		return $this->conn["slave"];
	}

	protected function ClientRedis($host,$port){
		$redis = new Redis();
        $redis->pconnect($host, $port);
        return $redis;
	}

	public function runCall($command,$params=[])
	{
		$redis = $this->GetRedisCall($command);
		if($redis){
			return $redis->{$command}(...$params);
		}else{
			return "连接Redis失败.";
		}
		
	}

	protected function GetRedisCall($command)
	{
		if(in_array($command,$this->call["write"])){
			$this->log = "连接主服务器：".$this->server["master"]["host"].":".$this->server["master"]["port"];
			$this->conn["master"] = $this->ClientRedis($this->server["master"]["host"],$this->server["master"]["port"]); 
			return $this->ConnMaster();

		}elseif(in_array($command,$this->call["read"])){

			$this->conn["slave"] = $this->ClientSlaveRedis();
			return $this->ConnSlave();

		}else{
			return "不支持该命令";
		}
	}

	protected function ClientSlaveRedis(){
		$slaves = $this->server["slaves"];
		$idx = mt_rand(0,count($slaves)-1);
		$this->log = "连接从服务器：".$slaves[$idx]["host"].":".$slaves[$idx]["port"];
		return $this->ClientRedis($slaves[$idx]["host"],$slaves[$idx]["port"]); 
	}

	

}
?>