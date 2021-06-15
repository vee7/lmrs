<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Api\V1\UserController;
use App\Model\Order;
use App\Model\Product;
use App\Model\ProductSku;

class SeckillOrderRequest extends FormRequest
{

    public function rules()
    {
        return [
            // 判断用户提交的地址 ID 是否存在于数据库并且属于当前用户
            // 后面这个条件非常重要，否则恶意用户可以用不同的地址 ID 不断提交订单来遍历出平台所有用户的收货地址
            'address_id' => [
                'required',
                Rule::exists('user_addr', 'id')->where('user_id', (new UserController())->userinfo()->original->id),
            ],
            'sku_id' => [
                'required',
                function ($attribute,$value,$fail){
                    if (!$sku = ProductSku::find($value)){
                        return $fail('该商品不存在');
                    }
                    if ($sku->product->type !== Product::TYPE_SECKILL){
                        return $fail('该商品不支持秒杀');
                    }
                    if ($sku->product->seckill->is_before_start){
                        return $fail('秒杀还未开始');
                    }
                    if ($sku->product->seckill->is_after_end){
                        return $fail('秒杀已结束');
                    }
                    if (!$sku->product->status){
                        return $fail('该商品未上架');
                    }
                    if ($sku->num < 1){
                        return $fail('该商品已售完');
                    }

                    if ($order = Order::query()
                    ->where('user_id',(new UserController())->userinfo()->original->id)
                    ->whereHas('items',function ($query) use ($value){
                        $query->where('product_sku_id',$value);
                    })
                    ->where(function ($query){
                        $query->whereNotNull('paid_at')->orWhere('closed','=',0);
                    })->first()){
                        if ($order->paid_at){
                            return $fail("你已经抢购了");
                        }
                        return $fail("你已经下单了，请支付");
                    }

                }
            ]
        ];
    }
}
