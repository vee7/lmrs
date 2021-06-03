<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use JWTAuth;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class ApiAuth extends BaseMiddleware
{
    /**
     * api.auth中间件验证token
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            //进行登录，成功则放行
            if($this->auth->parseToken()->authenticate()){
                return $next($request);
            }
            return response()->json(['status'=>420,'msg'=>'未登录']);
        } catch (TokenExpiredException $e) {
            //捕捉token过期，进行尝试token刷新
            try {
                $token = $this->auth->refresh();
                //使用一次性登录以保证此次请求的成功
                Auth::guard('api')->onceUsingId($this->auth->manager()->getPayloadFactory()->buildClaimsCollection()->toPlainArray()['sub']);
                auth()->onceUsingId($this->auth->manager()->getPayloadFactory()->buildClaimsCollection()->toPlainArray()['sub']);
            } catch (JWTException $e) {
              //捕捉到这个异常，表示无法刷新token，可能refresh也已经过期，此时只能让用户重新登陆
              return response()->json(['status'=>421,'msg'=>'身份验证失败，请重新登陆','error'=>$e->getMessage()]);
            }

        }
        // 在响应头中返回新的 token
        return $this->setAuthenticationHeader($next($request), $token);

    }
}
