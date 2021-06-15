<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SeckillProduct extends Model
{
      public $table = "seckill_products";
      protected $fillable = ['start_at','end_at'];
      protected $dates = ['start_at','end_at'];
      public $timestamps = false;

      public function product(){
          //关联商品表
          return $this->belongsTo(Product::class);
      }

      //判断秒杀开始或结束
      //is_before_start调用
    public function getIsBeforeStartAttribute()
    {
        //判断当前时间是否到了秒杀开始时间之内
        return Carbon::now()->lt($this->start_at);
    }

    public function getIsAfterEndAttribute()
    {
        //判断秒杀时间是否结束
        return Carbon::now()->gt($this->end_at);
    }
}
