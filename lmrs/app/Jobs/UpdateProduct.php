<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
//注意引入封装的类
use App\Services\RabbitmqService;

class UpdateProduct implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $productKey;
    protected $data;
    public function __construct($data)
    {
        $this->productKey = "product::info::".$data->id;
        RabbitmqService::push('update_queue',$data);
        $this->handle();
    }

    public function handle()
    {
        RabbitmqService::pop('update_queue',function ($message){
            $product = app('redis')->set($this->productKey,serialize($message));
            if (!$product){
                return;
            }
            //成功打印消息给控制台
            // print_r($message);
            return $message;
        });
    }

    public function failed(\Exception $exception)
    {
        //异常处理
        print_r($exception->getMessage());
    }
}
