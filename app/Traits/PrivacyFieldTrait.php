<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2019-03-13
 * Time: 19:17
 */

namespace App\Traits;


use App\Models\DataDictionarie;
use App\Repositories\PrivacyUserRepository;
use Illuminate\Support\Facades\Auth;


trait PrivacyFieldTrait
{
    /**
     * 艺人隐私字段
     * @param string $key
     * @param mixed $value
     * @return array|mixed
     * @author lile
     * @date 2019-03-13 18:57
     */
    public function setPrivacyField($module,$key, $value)
    {
        //如果不存在id，则判断为新建不加限制
        $id = $this->getAttribute('id');
        if (!$id){//不是隐私字段允许修改
            return $value;
        }
        //判断字段是否是隐私字段
        $privacy_field_list = DataDictionarie::getPrivacyFieldList();
        if (!in_array($module.".".$key,$privacy_field_list)){//如果该字段在隐私字段内,则判断是否有权限查看
            return $value;
        }
        $user = Auth::guard("api")->user();
        $has_power = PrivacyUserRepository::has_power($module,$key,$id,$user->id);//判断是否有权限查看该字段
        if($this->creator_id == $user->id || $has_power){//创建人可以修改有权限的可以修改
            return $this->getOriginal($key);
        }
        return $value;//有权限则修改
    }

    /**
     * 艺人隐私字段
     * @param string $key
     * @return mixed|string
     * @author lile
     * @date 2019-03-13 18:57
     */
    public function getPrivacyField($module,$key)
    {
        $fields = array_keys($this->attributes);
        if (!in_array($key,$fields)){
            $temp_key = ucwords(camel_case($key));
            $get_attribute = "get".$temp_key."Attribute";
            if(method_exists($this,$get_attribute)){
                return $this->$get_attribute();
            }
//            return $this->$key;
            return;
        }
        //判断字段是否是隐私字段
        $privacy_field_list = DataDictionarie::getPrivacyFieldList();
        if (!in_array($module.".".$key,$privacy_field_list)){//如果该字段在隐私字段内,则判断是否有权限查看
            return $this->attributes[$key];
        }
        $user = Auth::guard("api")->user();
        $id = $this->attributes['id'];
        $has_power = PrivacyUserRepository::has_power($module,$key,$id,$user->id);//判断是否有权限查看该字段
        if (!$has_power){ //没权限则不修改
            return "xxxxxx";
        }

        return $this->attributes[$key];//有权限则修改
    }
}