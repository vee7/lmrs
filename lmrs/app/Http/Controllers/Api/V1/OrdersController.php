<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\OrdersRequest;
// use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\UserController;
use App\Model\Order;
use App\Model\ProductSku;
use App\Model\UserAddr;
use Carbon\Carbon;

class OrdersController extends Controller
{
    public function store(OrdersRequest $request)
    {

        $user = (new UserController())->userinfo()->original->id;
        // return response()->json(['data'=>$user]);

        //开启事务
        $order = \DB::transaction(function () use ($user,$request){
            $address = UserAddr::find($request->input("address_id"));
            //更新用户地址的使用时间
            $address->last_time = Carbon::now();
            $address->save();

            //创建一个订单
            $order = new Order([
                'address' => [
                    'address' => "{$address->province}{$address->city}{$address->area}{$address->address}",
                    'zip' => $address->zip
                ],
                'remark' => $request->input('remark'),
                'total_amount' => 0
            ]);

            //订单与用户相关联
            $order->user()->associate($user);
            $order->save();

            $totalAmount = 0;
            $items = $request->input("items");

            foreach ($items as $data){
                $sku = ProductSku::find($data["sku_id"]);
                $item = $order->items()->make([
                   'amount' => $data["amount"],
                   'price'  =>  $sku->price
                ]);
                $item->product()->associate($sku->product_id);
                $item->productSku()->associate($sku);
                $item->save();
                $totalAmount += $sku->price * $data["amount"];
                if ($sku->decreaseStock($data["amount"]) <= 0){
                    throw new \RuntimeException("该商品库存不足");
                }
            }
            $order->update(["total_amount" => $totalAmount]);
            return $order;
        });
        return $order;
    }
}
