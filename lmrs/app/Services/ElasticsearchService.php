<?php
namespace App\Services;

use App\Model\ProductCategory;

class ElasticsearchService
{
    protected $params = [
        // "index" => 'products',
        "type" => "_doc",
        "body" => [
            "query" => [
              "bool" => [
                  'filter' => [],
                  'must'  => []
              ]
            ]
        ]
    ];

    //指定索引
    public function setIndex($idx)
    {
        $this->params['index'] = $idx;
        return $this;
    }

    //分页查询
    public function Paginate($size,$page)
    {
        $this->params['body']['from'] = ($page - 1) * $size;
        $this->params['body']['size'] = $size;
        return $this;
    }

    //筛选字段
    public function source($fields)
    {
        $fields = is_array($fields)?$fields:[$fields];
        $this->params['body']['_source'] = $fields;
        return $this;
    }

    //关键词查询
    public function Keywords($keywords)
    {
        if(is_array($keywords)==false){
            $keywords = [$keywords];
        }
        foreach ($keywords as $keyword) {
            $this->params['body']['query']['bool']['must'][] = [
                "multi_match" => [
                    "query" => $keyword,
                    "fields" => ['long_name^3','name^2','category^1']
                ]
            ];
        }

        return $this;
    }

    //判断商品状态
    public function isStatus()
    {
        $this->params['body']['query']['bool']['filter'][] = ['term' => ['status' => 1]];
        return $this;
    }

    //排序
    public function OrderBy($order)
    {
        $m = explode(',',$order);
        $this->params['body']['sort'] = [$m[0]=>$m[1]];
        return $this;
    }

    //分类查询
    public function Categorys($categorys)
    {

        $categorys_id = explode(',',$categorys);
        $category_id = array_pop($categorys_id);
        //$this->params['body']['debug'] =['category_id' => $category_id];
        $category_data = ProductCategory::where('id',$category_id)->first();
        if ($category_data->is_directory){
            //es中的商品需要先关联查询插入商品及分类信息
            $this->params['body']['query']['bool']['filter'][] = [
                'prefix' => ['category_path' => $category_data->path.$category_data->id.'-']
            ];
        }else{
            $this->params['body']['query']['bool']['filter'][] = [
              'term' => ['category_id' => $category_data->id]
            ];
        }

        return $this;
    }

    //分面搜索 aggs表示聚合查询,老师写的，暂时不用，感觉代码有问题，也不适用于属性筛选
    public function attributeSearch()
    {
        $this->params["body"]["aggs"] = [
            'properties' => [
                "nested" => [
                    'path' => "attributes"
                ],
                'aggs' => [
                    'properties' => [
                        'terms' => [
                            'field' => 'attributes.name',
                        ],
                    ],
                    'aggs' => [
                        'properties' => [
                            'terms' => [
                                'field' => 'attributes.value',
                            ]
                        ]
                    ]
                ]
            ]
        ];
        return $this;
    }

    //筛选属性 nested表示查询嵌套数据
    public function AttributesFilter($name,$value)
    {
      $this->params["body"]["query"]["bool"]["filter"][] = [
          'nested' => [
              'path' => 'attributes',
              'query' => [
                  "bool" => [
                      "must" => [
                          ['term' => ['attributes.value' => $value]],
                          ['term' => ['attributes.name' => $name]]
                      ],
                  ]
              ]
          ]
      ];
      return $this;
    }

    //通过id查询商品
    public function ProductIdSearch($id)
    {
        $this->params["body"]["query"]["bool"]["filter"][] = [
            "term" => [
                "id" => $id
            ]
        ];
        return $this;
    }

    public function GetParams(){
        return $this->params;
    }
}
