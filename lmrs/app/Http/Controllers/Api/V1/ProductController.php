<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Product;
use App\Model\ProductCategory;
use App\Services\ElasticsearchService;
use Elasticsearch\ClientBuilder;
use DB;
use Illuminate\Support\Facades\Redis;

class ProductController extends Controller
{
    /**
     * api查询商品筛选
     * [作者]：vee7z
     * [邮箱]：547966838@qq.com
     * [QQ]：547966838
     * @param  Request $request [description]
     * @return json           [description]
     */
    public function productCategoryList(Request $request)
    {
        //查询用户筛选的分类id，可以是多个
        $categorys = ProductCategory::whereIn('id',explode(',',$request->input('categorys_id')))->get();
        //用左连接关联两个表进行查询
        $builder = DB::table('products')->leftjoin('product_categorys as categorys',function($join) use ($categorys){
            //通过什么字段进行关联
            $join->on('products.three_category_id','=','categorys.id');
        });
        //循环添加where条件，第一个条件是and，后面的是or
        $builder->where('products.status',true);

        foreach ($categorys as $key => $value) {
          //如果查询的是最底层分类，直接匹配分类id
          if($value->is_directory == 0){
              $builder->where('three_category_id',$value->id);
          }else{
              //第一个where子句采用and方法，后面的采用or方法
              if($key == 0){
                  $builder->where('path','like',$value->path.$value->id.'-%');
              }else{
                  $builder->Orwhere('path','like',$value->path.$value->id.'-%');
              }
          }
        }

        //前端传入格式为price_asc,soldcount_desc的排序参数，进行分割，加入到查询语句中
        if($request->input('order')){
            $m = explode('_',$request->input('order'));
            //为保证高可用，用switch进行字段限制
            switch ($m[0]) {
              case 'price':
                $builder->orderBy('price',$m[1]);
                break;
              case 'soldcount':
                $builder->orderBy('sold_count',$m[1]);
                break;
              case 'lasttime':
                $builder->orderBy('products.last_time',$m[1]);
                break;
              default:
                break;
            }
        }
        $data = $builder->paginate(5);
        return response()->json([
          'status' => 200,
          'data' => $data
        ]);
    }

    //通过es查询
    public function EsProductCategoryList(Request $request,ElasticsearchService $builder)
    {
        //设置查询索引
        $builder->setIndex('products');

        //筛选只上架的商品
        // $builder->isStatus();


        //设置分页
        $perpage = $request->input('perpage',3);
        $page = $request->input('page',1);
        $builder->Paginate($perpage,$page);

        //设置排序 格式 price,desc
        if($request->input('order')){
            $builder->OrderBy($request->input('order'));
        }

        //筛选分类
        if($request->input('categorys_id')){
            $builder->Categorys($request->input('categorys_id'));
        }

        //关键词筛选
        if($request->input('keywords')){
            $keywords = array_filter(explode(' ',$request->input('keywords')));
            $builder->Keywords($keywords);
        }

        //通过属性查商品，属性筛选 格式 类型:官方标配,颜色:皓月银
        //http://192.168.137.77:8080/api/EsProductCategoryList?page=1&order=price,desc&categorys_id=440&attributes=类型:官方标配,颜色:皓月银
        if($request->input('attributes')){
            $attributes = explode(',',$request->input('attributes'));// [类型:官方标配,颜色:皓月银]
            foreach ($attributes as $attribute) {
                $attribute_value = explode(':',$attribute);
                $builder->AttributesFilter($attribute_value[0],$attribute_value[1]);// AttributesFilter(类型,官方标配)
            }

        }

        $result = app('es')->search($builder->GetParams());
        //$result = $builder->GetParams();
        return response()->json([
         'result'=>$result
        ]);

    }

    //通过分类查询对应属性
    //http://192.168.137.77:8080/api/CategoryAttributesList?category_id=440
    public function CategoryAttributesList(Request $request,ElasticsearchService $builder)
    {
        //设置查询的索引
        $builder->setIndex('attributes');

        //筛选分类
        if($request->input('category_id')){
            $builder->Categorys($request->input('category_id'));
        }

        //过滤字段
        //$builder->source(['name']);
        $result = app('es')->search($builder->GetParams());
         // return $builder->GetParams();

        //获取es中数据后，取出有用数据
        $datasName = [];
        foreach ($result['hits']['hits'] as $key => $value) {
            $datas[] = $value['_source'];
            //得到所有属性名，由于是一对一，因此包含重复
            $datasName[] = $value['_source']['name'];
        }

        //属性名去重
        $datasName = array_unique($datasName);
        // //循环遍历，属性名相同的放在同一数组下,变成一对多（属性对属性值）方便前端调用
        $uniqueResult = [];
        foreach ($datasName as $key => $dataName) {
            foreach ($datas as $data) {
                if($dataName == $data['name']){
                    $uniqueResult[$dataName][] = $data;
                }
            }
        }

        return response()->json([
         'result'=>$uniqueResult
         //'debug'=>$uniqueResult
        ]);

    }

    //通过es查询商品信息，7天访问超过1万则缓存到redis
    //定义变量 商品信息redis的Key
    protected $productKey;
    //定义变量 商品访问量计数器Key
    protected $productCountKey;
    public function productInfo(Request $request,ElasticsearchService $builder)
    {
        if($product_id = $request->input("product_id"))
        {
            $this->productKey = "product::info::data::".$product_id;
            $this->productCountKey = "product::info::count::".$product_id;
            //先查询redis是否有数据，有则直接返回
            $productInfo = Redis::get($this->productKey);
            if($productInfo == "" || $productInfo == false){
                //如果未查到，查询es
                $builder->setIndex("products");
                $builder->ProductIdSearch($product_id);
                $result = app('es')->search($builder->GetParams());
                // $productInfo = collect($result["hits"]["hits"])->pluck('_source')->all();
                foreach ($result['hits']['hits'] as $data) {
                    $productInfo[] = $data["_source"];
                }

                //判断是否已经设置过计数，是则判断是否7天超过1万，是则缓存商品信息到redis
                $exsits = Redis::set($this->productCountKey,1,"NX","EX",10080);
                //null表示已经设置过
                if($exsits == null){
                    if(Redis::incr($this->productCountKey) > 10){
                        Redis::set($this->productKey,serialize($productInfo),"EX",100800);
                    }
                }

                return response()->json($productInfo[0]);
            }else{
                return response()->json(unserialize($productInfo)[0]);
            }


        }else{
            return response()->json([
                "status"=>430,
                "msg"=>"未找到商品id"
            ]);
        }
    }

    public function pool()
    {
        //用连接池的方式操作Redis
        $result = app('redis')->get("lmrs_home_index");
        // $result = "123";

        return response()->json($result);
    }
}
