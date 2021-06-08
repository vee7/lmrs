<?php

namespace App\Http\Requests\Api\V1;

use App\Model\ProductSku;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Api\V1\UserController;

class OrdersRequest extends FormRequest
{

    // 传递为json数据，示例：
    // {
    //   "address_id":1,
    //   "remark":"这是备注"
    //   "iteam":{
    //     "0":{
    //       "sku_id":400001,
    //       "amount":10
    //     },
    //     "1":{
    //       "sku_id":400002,
    //       "amount":3
    //     }
    //   }
    // }
    public function rules()
    {
        return [
            'address_id' => [
                'required',
                //判断user_addr表中，user_id等于当前用户id的数据是否存在，也就是判断这个地址是不是属于当前用户的
                Rule::exists('user_addr','id')->where('user_id',(new UserController())->userinfo()->original->id),
            ],
            'items' => ['required','array'],


            'items.*.sku_id' => [//检查items数组每个sku的数据
                'required',
                function ($attribute,$value,$fail){
                    if (!$sku = ProductSku::find($value)){
                        return $fail("该商品不存在");
                    }
                    if ($sku->status === 0 or !$sku->product->status === 0){
                        return $fail("该商品未上架");
                    }
                    if ($sku->num === 0){
                        return $fail("该商品已售完");
                    }
                    // 获取当前索引
                    preg_match('/items\.(\d+)\.sku_id/', $attribute, $m);
                    $index = $m[1];
                    // 根据索引找到用户所提交的购买数量
                    $amount = $this->input('items')[$index]['amount'];
                    if ($amount > 0 && $amount > $sku->num) {
                        return $fail('该商品库存不足');
                    }
                }
            ],
            'items.*.amount' => ['required', 'integer', 'min:1'],
        ];
    }
}
