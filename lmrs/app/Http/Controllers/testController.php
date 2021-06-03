<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use \App\Model\Test as ModelTest;
use \App\Support\Test as SupportTest;

class testController extends Controller
{

    public function index(){
      return view('test');
    }

    public function upload(Request $request)
    {
      if ($request->hasFile('picture')) {
        $picture = $request->file('picture');
        if (!$picture->isValid()) {
            abort(400, '无效的上传文件');
        }
        // 文件扩展名
        $extension = $picture->getClientOriginalExtension();
        // 文件名
        $fileName = $picture->getClientOriginalName();
        // 生成新的统一格式的文件名
        $newFileName = md5($fileName . time() . mt_rand(1, 10000)) . '.' . $extension;
        // 图片保存路径
        $savePath = 'images/' . $newFileName;
        // Web 访问路径
        $webPath = '/storage/' . $savePath;
        // 将文件保存到本地 storage/app/public/images 目录下，先判断同名文件是否已经存在，如果存在直接返回
        if (Storage::disk('public')->has($savePath)) {
            return response()->json(['path' => $webPath]);
        }
        // 否则执行保存操作，保存成功将访问路径返回给调用方
        if ($picture->storePubliclyAs('images', $newFileName, ['disk' => 'public'])) {
            return response()->json(['path' => $webPath]);
        }
        abort(500, '文件上传失败');
      } else {
        abort(400, '请选择要上传的文件');
      }
    }

    //  控制器直接操作
    // public function reg(){
    //   $regObj = new ModelTest;
    //   $regObj->username = 'yhw547966838';
    //   $regObj->nickname = 'vee7z';
    //   $regObj->password = md5('yhw1230');
    //   $regResult = $regObj->save();
    //   if($regResult){
    //     return "注册成功";
    //   }else{
    //     return "注册失败";
    //   }
    // }

    //  调用接口操作
    public function reg(SupportTest $supportTest){
      $result = $supportTest->reg();
      if($result){
        return response()->json([
          "code" => 200,
          "msg" => "接口方式注册成功"
        ]);
      }else{
        return response()->json([
          "code" => 400,
          "msg" => "接口方式注册失败"
        ]);
      }
    }
    public function show(SupportTest $supportTest){
      $result = $supportTest->show();
      if($result){
        return response()->json([
          "code" => 200,
          "msg" => "获取全部成功",
          "data"  => $result
        ]);
      }else{
        return response()->json([
          "code" => 200,
          "msg" => "接口方式获取全部数据失败"
        ]);
      }
    }
    public function get(SupportTest $supportTest,Request $request){
      //return $request->route('id');
      $result = $supportTest->get($request->route('id'));
      if($result){
        return response()->json([
          "code" => 200,
          "msg" => "获取指定数据成功",
          "data"  => $result
        ]);
      }else{
        return response()->json([
          "code" => 400,
          "msg" => "接口方式获取指定数据失败"
        ]);
      }
    }
    public function edit(SupportTest $supportTest,Request $request){
      $result = $supportTest->edit($request->route('id'));
      if($result){
        return response()->json([
          "code" => 200,
          "msg" => "修改成功"
        ]);
      }else{
        return response()->json([
          "code" => 400,
          "msg" => "接口方式修改失败"
        ]);
      }
    }
    public function del(SupportTest $supportTest,Request $request){
      $result = $supportTest->del($request->route('id'));
      if($result){
        return response()->json([
          "code" => 200,
          "msg" => "接口方式删除成功"
        ]);
      }else{
        return response()->json([
          "code" => 400,
          "msg" => "接口方式删除失败"
        ]);
      }
    }

}
