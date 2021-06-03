<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

$api = app('Dingo\Api\Routing\Router');
$api->version('v1',["middleware" => ["bindings"],"namespace" => "App\Http\Controllers\Api\V1"],function ($api){
  //验证码路由
  $api->post('getverfycode','UserController@verfy')->name("user.verfy");
  //会员注册路由
  $api->post('reg','UserController@reg')->name("user.reg");
  //登录路由
  $api->post('login','UserController@login')->name("user.login");
  //获取日销量排行榜
  $api->post('IndexProductQueue','IndexController@IndexProductQueue')->name("index.IndexProductQueue");

  //该路由组下需要进行jwt认证才可访问
  //认证方法：get方法或header头bearer Token中加入"token"才可以访问,注意get方法里是小写
  $api->group(['middleware'=>'jwt.auth'],function($api){
    //退出登录
    $api->post('logout','UserController@logout')->name("user.logout");
    //刷新token
    $api->post('refresh','UserController@refresh')->name("user.refresh");
    //获取用户信息
    //如http://192.168.137.77:8080/api/userinfo?token=.......
    $api->get('userinfo','UserController@userinfo')->name("user.userinfo");
  });

  //自定义中间件验证登录，刷新token
  $api->group(['middleware'=>'api.auth'],function($api){
    //获取商品信息
    //$api->post('productCategoryList','ProductController@productCategoryList')->name("product.productCategoryList");
  });
  $api->get('productCategoryList','ProductController@productCategoryList')->name("product.productCategoryList");
  $api->get('EsProductCategoryList','ProductController@EsProductCategoryList')->name("product.EsProductCategoryList");
  $api->get('CategoryAttributesList','ProductController@CategoryAttributesList')->name("product.CategoryAttributesList");
  $api->post('productInfo','ProductController@productInfo')->name("product.productInfo");
});
