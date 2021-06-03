<?php
class RedisMS
{
	protected $server;
	protected $conn;
	public $log = array();
  protected $sentinelConn;
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
		if($this->server["is_open"]!=True){
			try {
				$this->conn["master"] = $this->ClientRedis($this->server["master"]["host"], $this->server["master"]["port"]);
				$this->conn["slave"] = $this->ClientRedis($this->server["master"]["host"], $this->server["master"]["port"]);
				array_push($this->log,"主从配置未开启，直接操作主节点");
				array_push($this->log,"主节点：".$this->server["master"]["host"].":".$this->server["master"]["port"]);
			} catch (\Exception $e) {
				array_push($this->log,"主从配置未开启，主节点连接失败");
				array_push($this->log,"主节点：".$this->server["master"]["host"].":".$this->server["master"]["port"]);
			}
		}elseif($this->server["is_sentinel"]==True){
          //初始化哨兵连接下标
          $sentinelIdx = 0;
          $this->ClientSentinel($sentinelIdx);

    }
	}

  //循环遍历连接哨兵
  public function ClientSentinel($sentinelIdx){
    try {
        $this->sentinelConn = new Redis();
        array_push($this->log,"连接哨兵：".$this->server["sentinel"][$sentinelIdx]["host"].":".$this->server["sentinel"][$sentinelIdx]["port"].":".$sentinelIdx);
        return $this->sentinelConn->connect($this->server["sentinel"][$sentinelIdx]["host"], $this->server["sentinel"][$sentinelIdx]["port"]);
    } catch (\Exception $e) {
      if($sentinelIdx > (count($this->server["sentinel"])-1)){
        $this->conn["master"] = "连接哨兵失败";
        $this->conn["slave"] = "连接哨兵失败";
				array_push($this->log,"连接哨兵失败");
      }else{
        $sentinelIdx++;
        $this->ClientSentinel($sentinelIdx);
      }
    }
  }

	public function ConnMaster()
	{
    //return $this->log;
		return $this->conn["master"];
	}

	public function ConnSlave()
	{
    //return $this->log;
    return $this->conn["slave"];
	}

	protected function ClientRedis($host,$port){
    try {
      $redis = new Redis();
      $redis->pconnect($host, $port);
      return $redis;
    } catch (\Exception $e) {
      return False;
    }
	}

	public function runCall($command,$params=[])
	{
		if ($this->server["is_open"]==True) {
			$redis = $this->GetRedisCall($command);
		}else{
			$redis = $this->ConnMaster();
		}

		if($redis){
			return $redis->{$command}(...$params);
		}else{
			return "连接Redis失败.";
		}
	}

	public function GetRedisCall($command)
	{
		if ($this->server["is_open"]==True) {
			if(in_array($command,$this->call["write"])){
	      if($this->server["is_sentinel"]==True){
	        try {
	          //通过哨兵获取主服务器信息
	          $masterInfo = $this->sentinelConn->rawCommand('SENTINEL', 'master', $this->server['masterName']);
	          $master = $this->parseArrayResult($masterInfo);
	          //连接主服务器
	          array_push($this->log, "连接主服务器(哨兵模式)：".$master["ip"].":".$master["port"]);
	    			$this->conn["master"] = $this->ClientRedis($master["ip"],$master["port"]);
	    			return $this->ConnMaster();
	        } catch (\Exception $e) {
	          return false;
	        }
	      }else{
	        try {
	          //连接主服务器
	          array_push($this->log, "连接主服务器(配置模式)：".$this->server["master"]["host"].":".$this->server["master"]["port"]);
	    			$this->conn["master"] = $this->ClientRedis($this->server["master"]["host"], $this->server["master"]["port"]);
	    			return $this->ConnMaster();
	        } catch (\Exception $e) {
	          return false;
	        }
	      }

			}elseif(in_array($command,$this->call["read"])){

				$this->conn["slave"] = $this->ClientSlaveRedis();
				return $this->ConnSlave();

			}else{
				return "不支持该命令";
			}
		}
	}

	protected function ClientSlaveRedis()
	{
		if ($this->server["is_open"]==True) {
	    if($this->server["is_sentinel"]==True){
	      try {
	        //从哨兵获取从节点信息
	        $slavesInfo = $this->sentinelConn->rawCommand('SENTINEL', 'slaves', $this->server['masterName']);
	        $slaves = array();
	        for($i=0;$i<count($slavesInfo);$i++){
	          array_push($slaves,$this->parseArrayResult($slavesInfo[$i]));
	        }
	        //连接从节点
	    		$idx = mt_rand(0,count($slaves)-1);
	    		array_push($this->log, "连接从服务器(哨兵模式)：".$slaves[$idx]["ip"].":".$slaves[$idx]["port"]);
	    		return $this->ClientRedis($slaves[$idx]["ip"],$slaves[$idx]["port"]);
	      } catch (\Exception $e) {
	        return false;
	      }

	    }else{
	      try {
	        //从配置获取从节点信息
	    		$slaves = $this->server["slaves"];
	        //连接从节点
	    		$idx = mt_rand(0,count($slaves)-1);
	    		array_push($this->log, "连接从服务器(配置模式)：".$slaves[$idx]["host"].":".$slaves[$idx]["port"]);
	    		return $this->ClientRedis($slaves[$idx]["host"],$slaves[$idx]["port"]);
	      } catch (\Exception $e) {
	        return false;
	      }

	    }
		}
	}

  protected function parseArrayResult(array $data)
  {
      $result = array();
      $count = count($data);
      for ($i = 0; $i < $count;) {
          $record = $data[$i];
          if (is_array($record)) {
              $result[] = parseArrayResult($record);
              $i++;
          } else {
              $result[$record] = $data[$i + 1];
              $i += 2;
          }
      }
      return $result;
  }



}
?>
