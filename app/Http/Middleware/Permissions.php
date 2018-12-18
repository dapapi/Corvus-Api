<?php


namespace App\Http\Middleware;
use Closure;
use App\User;
use Illuminate\Support\Facades\Auth;
use App\Models\DataDictionarie;
use App\Models\RoleUser;
use App\Models\RoleResource;


use Illuminate\Http\Request;
use Fideloper\Proxy\TrustProxies as Middleware;

class Permissions
{
    public function handle($request, Closure $next, $guard = null)
    {
//        $userId = $request->user('api')->id ?? 0;
//
//        $name = $request->path();
//        $urlValue = preg_replace('/\\d+/', '{id}', $name);
//        $urlMethod = $request->method();
//
//        //根据url地址查功能ID
//        $featureArr = DataDictionarie::where('val', '/'.$urlValue)->where('code', $urlMethod)->get()->toArray();
//
//        //根据用户id获取角色id
//        $roleInfo = RoleUser::where('user_id', $userId)->get()->toArray();
//
//        if(!empty($roleInfo)){
//            //根据角色获取功能id
//            //$roleInfo = RoleResource::where('role_id', $roleId)->get()->toArray();
//            if (!empty($featureArr)) {
//
//                foreach ($roleInfo as $value){
//                    $arrRoleId[] = $value['role_id'];
//                }
//                 $featureInfo = RoleResource::whereIn('role_id', $arrRoleId)->where('resouce_id', $featureArr[0]['id'])->get()->toArray();
//
//                if (!empty($featureInfo)) {
//                    return $next($request);
//                } else {
//                    echo "您没有访问权限,请联系管理员";
//                    exit;
//                }
//            } else {
//                echo "请求url地址错误";
//                exit;
//
//            }
//        }else{
//            echo "该用户没有角色"; exit;
//        }
        return $next($request);
    }
}
