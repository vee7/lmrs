<?php
namespace App\Support;

use App\Contracts\Test as Contracts;
use \App\Model\Test as ModelTest;
use Illuminate\Contracts\Container\Container;


class Test implements Contracts
{

  function __construct(Container $container)
  {
    $this->app = $container;
  }

  public function reg(){
      $regObj = new ModelTest;
      $regObj->username = 'yhw547966838';
      $regObj->nickname = 'vee7z';
      $regObj->password = md5('yhw1230');
      $regResult = $regObj->save();
      if(!$regResult){
        return False;
      }else{
        return True;
      }
  }

  public function show(){
      $regResult = ModelTest::all();
      if(!$regResult){
        return False;
      }else{
        return $regResult;
      }

  }

  public function get($id){
      $regResult = ModelTest::where('id',$id)->get();
      if(!$regResult || $regResult->isEmpty()){
        return "未找到数据";
      }else{
        return $regResult;
      }
  }

  public function del($id){
      $regResult = ModelTest::destroy([$id]);
      if(!$regResult){
        return False;
      }else{
        return True;
      }
  }

  public function edit($id){
    try {
      $regObj = ModelTest::find($id);
      $regObj->nickname = 'vee7z change';
      $regResult = $regObj->save();
      if(!$regResult){
        return False;
      }else{
        return True;
      }
    } catch (\Exception $e) {
        return False;
    }

  }


}
