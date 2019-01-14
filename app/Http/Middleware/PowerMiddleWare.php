<?php

namespace App\Http\Middleware;

use App\Exceptions\NoFeatureInfoException;
use App\Exceptions\NoRoleException;
use App\Models\RoleUser;
use App\Repositories\ScopeRepository;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PowerMiddleWare
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = Auth::guard('api')->user();
        $userId = $user->id;
        //获取用户角色
        $roleList = RoleUser::where('user_id',$userId)->select('role_id')->get();
        if(count($roleList->toArray()) == 0) {
            throw new NoRoleException("你没有角色，请联系管理员");
        }
        $role_list = array_column($roleList->toArray(),'role_id');
        $preg = "/{[a-z]+}/";
        $uri = $request->route()->uri;
        $model = null;
        if(preg_match($preg,$uri,$model)){//放过了没有携带model的访问，例如新增
            $model = $model[0];
            $model = trim($model,"{");
            $model = trim($model,"}");
            $model = $request->$model;
        }
        $operation = preg_replace('/\\d+/', '{id}', $request->path());
        $method = $request->method();
        try{
            (new ScopeRepository())->checkPower($operation,$method,$role_list,$model);
        }catch(NoFeatureInfoException $e){
            Log::error($e);
            return [];
        }


        return $next($request);
    }
}
