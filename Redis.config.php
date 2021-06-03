<?php

$server = [
	//是否开启主从
	"is_open"=>True,
	//是否开启哨兵
	"is_sentinel"=>True,
	//
	"masterName"=>"mymaster",
	"sentinel"=>[
		["host"=>"192.168.138.180","port"=>"26380"],
		["host"=>"192.168.138.181","port"=>"26381"],
		["host"=>"192.168.138.182","port"=>"26382"],
	],
	"master"=>["host"=>"192.168.138.77","port"=>6379],
	"slaves"=>[
		["host"=>"192.168.138.100","port"=>6379],
		["host"=>"192.168.138.101","port"=>6379]
	]
];

?>
