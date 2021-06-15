<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Model\Order;

class CloseOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;
    public function __construct(Order $order,$time)
    {
        $this->order = $order;
        $this->delay($time);
    }

    public function handle()
    {
        //如果已支付则不处理
        if($this->order->paid_at){
            return;
        }
        //否则关闭订单
        \DB::transaction(function(){
            //关闭订单
            $this->order->update(['closed'=>1]);
            //订单中的库存返回去
            foreach ($this->order->items as $item) {
                $item->productSku->addStock($item->amount);
            }
        });
    }
}
