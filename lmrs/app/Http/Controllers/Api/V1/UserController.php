<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Contracts\Sms;
use Illuminate\Support\Facades\Redis;
use App\Model\User;
use Illuminate\Support\Facades\Hash;


class UserController extends Controller
{
    //发送验证码
    public function verfy(Request $request,Sms $sms){
      //构建参数并发送
      $phone = $request->phone;
      $code = str_pad(random_int(1,9999),4,0,STR_PAD_LEFT);
      $content = "你的短信验证码为：".$code;
      try {
        $checkRedis = $this->checkRedis($phone);
        if(!$checkRedis['result']){
          return response()->json([
            'statu' => 401,
            'msg' => $checkRedis['msg'],
          ]);
        }
        // $result = $sms->send($phone,$content);
        $result = "模拟发送成功";
      } catch (\Exception $e) {
        return response()->json([
          'statu' => 400,
          'msg' => '短信发送失败',
          'error' => $e->getMessage()
        ]);
      }

      //构建缓存key并缓存，用户注册时验证
      $cacheKey = "key::verfy::".$phone."::".$code;
      $cacheVal = [
        'phone'=>$phone,
        'code'=>$code
      ];
      $exp = now()->addMinutes(3);
      \Cache::put($cacheKey,$cacheVal,$exp);

      //发送成功返回key,注意提交注册时前端要动态将key传到表单里，以便注册验证
      return response()->json([
        'statu' => 200,
        'verfyKey' => $cacheKey,
        'msg' =>'服务商返回信息:'.$result
      ]);
    }

    //缓存到redis
    public function checkRedis($phone){
      //构建redis key并缓存，用于防刷机制，存在则不允许发送
      try {
        $redisKey = "key::".$phone;
        $is_exists = Redis::set($redisKey,1,"EX",60,"NX");
        if($is_exists !=null || Redis::incr($redisKey)<=5){
          $result = [
            'result'=>true,
            'msg'=>'请求成功'
          ];
          return $result;
        }else{
          $result = [
            'result'=>false,
            'msg'=>'请求频繁，请1分钟后再试'
          ];
          return $result;
        }
      } catch (\Exception $e) {
        $result = [
          'result'=>false,
          'msg'=>'请求失败，系统异常redis设置失败'
        ];
        return $result;
      }

    }

    //提交注册
    public function reg(Request $request){
      //通过发送验证码处生成的key获取缓存中的手机号和验证码
      $verfyData = \Cache::get($request->verfyKey);
      if(!$verfyData){
        return response()->json([
          'statu' => 410,
          'msg' =>'验证码已经失效，请重新获取'
        ]);
      }
      //匹配提交的验证码与缓存中的验证码是否一致
      if(!hash_equals($request->verfyCode,$verfyData['code'])){
        return response()->json([
          'statu' => 411,
          'msg' =>'验证码不正确，请重新输入'
        ]);
      }
      //查询手机号是否已经存在
      $is_userExists = User::where('mobile',$verfyData['phone'])->first();
      if($is_userExists){
        return response()->json([
          'statu' => 420,
          'msg' =>'该手机号已经注册过账户，请直接登录'
        ]);
      }
      //账号入库
      $user = User::create([
        'name'=>$request->name,
        'password'=>Hash::make($request->password),
        'mobile'=>$verfyData['phone'],
        'create_time'=>date('Y-m-d H:i:s'),
      ]);
      //入库成功清除缓存
      \Cache::forget($request->verfyKey);

      //返回登陆成功信息，也可以返回给资源修改器重构数据结构
      //如：new UserResource($user);

      //获取token存入redis并返回给前端
      $token = auth('api')->login($user);
      return $this->setRedisUserInfo($token,'注册成功');

    }

    public function userinfo()
    {
      $data = auth('api')->user();
      if($data->status_code==401){
        return response()->json([
          'statu' => 421,
          'msg' =>'用户认证失败，请重新登陆'
        ]);
      }
      return response()->json($data);
    }

    //登录
    public function login(Request $request){
      //只获取请求里的name和password字段用于jwt认证
      $loginInfo = $request->only(['name','password']);
      $token = auth('api')->attempt($loginInfo);
      //返回false表示认证失败
      if(!$token){
        return response()->json([
          'statu' => 421,
          'msg' =>'账户名或密码不正确，请确认后重试'
        ]);
      }
      //登陆成功，设置redis缓存并返回成功信息
      return $this->setRedisUserInfo($token,'登录成功');
    }

    //刷新token
    public function refresh(){
      $newToken = auth('api')->refresh();
      //刷新token后同时刷新redis用户信息
      return $this->setRedisUserInfo($newToken,'刷新成功');
    }

    //退出登录
    public function logout(){
      $user = $this->userinfo();
      $userInfoKey = "User::Info::".$user->original->id;
      auth('api')->logout();
      Redis::del($userInfoKey);
      return response()->json([
        'statu' => 200,
        'msg' =>'退出成功',
      ]);
    }

    public function setRedisUserInfo($token,$msg){
      //通过jwt获取用户信息并Redis缓存用户信息3600秒
      $user = $this->userinfo();
      $userInfoKey = "User::Info::".$user->original->id;
      Redis::set($userInfoKey,serialize($user->original),"EX",3600);
      //获取可以通过 $user = unserialize(Redis::get($key));echo $user->id;

      //返回成功并传递token等信息给前端
      return response()->json([
        'statu' => 200,
        'msg' => $msg,
        'verfyToken' => $token,
        //获取过期时间(分钟*60)
        'expires_in' => auth('api')->factory()->getTTL()*60,
        'userInfo' => $user->original
      ]);
    }
}
