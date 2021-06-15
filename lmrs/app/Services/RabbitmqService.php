<?php
namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitmqService
{
    public static function getConnect()
    {
        $config = [
            'host' => env('RABBITMQ_HOST', '127.0.0.1'),
            'port' => env('RABBITMQ_PORT', 5672),
            'user' => env('RABBITMQ_USER', 'root'),
            'password' => env('RABBITMQ_PASSWORD', 'root'),
            'vhost' => env('RABBITMQ_VHOST', '/'),
        ];

        return new AMQPStreamConnection($config["host"],$config["port"],$config["user"],$config["password"],$config["vhost"]);
    }

    public static function push($queue,$messageBody,$exchange='router')
    {
        $connection = self::getConnect();
        $channel = $connection->channel();
        //声明一个队列
        $channel->queue_declare($queue,false,true,false,false);
        $channel->exchange_declare($exchange,'direct',false,true,false);
        $channel->queue_bind($queue,$exchange);
        $message = new  AMQPMessage($messageBody,array('content_type' => 'text/plain', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
        $channel->basic_publish($message,$exchange);
        $channel->close();
        $connection->close();
    }

    public static function pop($queue,$callback,$exchange='router')
    {
        $connection = self::getConnect();
        $channel = $connection->channel();
        $message = $channel->basic_get($queue);
        $res = $callback($message->getBody());
        if ($res){
            $channel->basic_ack($message->getDeliveryTag());
        }
        print_r($res);
        $channel->close();
        $connection->close();
    }
}
