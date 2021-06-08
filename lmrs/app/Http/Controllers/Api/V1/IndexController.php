<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Product;
use Illuminate\Support\Facades\Redis;
use App\Jobs\TestJob as JobsQueue;
use Illuminate\Support\Facades\Log;

class IndexController extends Controller
{
    //返回日销量排行榜
    public function IndexProductQueue(Request $request){
      //获取有序集合的值，倒叙排列(将商品id作为值，销量作为分数存入有序队列)
      $ProductSoldCountId = Redis::zrevrange('Index::Product::Queue',0,-1);

      if($ProductSoldCountId == null or $ProductSoldCountId == ""){
        //如果有序集合里没有数据，说明已经过期，从mysql取出数据5条
        $ProductSoldCountData = Product::query()->select("id","name","sold_count")->orderBy("sold_count","desc")->limit(5)->get();
        //将数据再次存入有序集合缓存24小时，利用管道一次性提交，减少循环提交命令的消耗
        Redis::pipeline(function($redis) use ($ProductSoldCountData){
          //删除原来的缓存，确保高可用
          $redis->del('Index::Product::Queue');
          //循环存入有序集合，并且循环设置每个id对应的值
          foreach ($ProductSoldCountData as $value) {
            $redis->zadd('Index::Product::Queue',$value['sold_count'],$value['id']);
            $redis->set('Index::Product::Queue::'.$value['id'],serialize($value),"EX",86400);
          }
          $redis->expire("index::product::queue",86400);
        });
      }else{
        //如果有序集合有数据，代表缓存还没过期，直接取出并循环获取id对应的数据
        $ProductSoldCountData = [];
        foreach ($ProductSoldCountId as $i => $value) {
          $ProductSoldCountData[$i] = unserialize(Redis::get("Index::Product::Queue::".$value));
        }
      }
      return response()->json([
        "data" => $ProductSoldCountData,
        "status" => 200
      ]);
    }

    //测试rabbitmq队列
    public function queue(){
      for ($i=0; $i < 5; $i++) {
        Log::info('Start: ' . date('Y-m-d H:i:s', time()));
        $arr = ['time' => time(), 'id' => rand(100, 999)];

        //$this->dispatch(new JobsQueue($arr));
        JobsQueue::dispatch($arr);
        Log::info('End: ' . date('Y-m-d H:i:s', time()));
      }

      return 'success';
    }
}
