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
    public function setAttribute($key, $value)
    {
        //判断当前要设置的字段是否是隐私字段
        $privacy_field_list = DataDictionarie::getPrivacyFieldList();
        if (!in_array($this->getMorphClass().".".$key,$privacy_field_list)){//如果该字段不在在隐私字段内
            return parent::setAttribute($key,$value);//调用model模型的set魔术方法
        }
        $user = Auth::guard("api")->user();
        $id = array_key_exists('id',$this->attributes) ? $this->attributes['id'] : null;
        if ($id){
            $has_power = PrivacyUserRepository::has_power($this->getMorphClass(),$key,$id,$user->id);//判断是否有权限查看该字段
        }else{
            $has_power = true;
        }

        if($this->creator_id == $user->id || $has_power){//创建人可以修改有权限的可以修改
            return parent::setAttribute($key,$value);//调用model模型的set魔术方法
        }
        //没有权限不调用model模型的魔术方法进行修改，即不做任何操作
    }

    /**
     * 艺人隐私字段
     * @param string $key
     * @return mixed|string
     * @author lile
     * @date 2019-03-13 18:57
     */
    public function getAttribute($key)
    {
        //判断当前要设置的字段是否是隐私字段
        $privacy_field_list = DataDictionarie::getPrivacyFieldList();
        if (!in_array($this->getMorphClass().".".$key,$privacy_field_list)){//如果该字段不在在隐私字段内
            return parent::getAttribute($key);//调用model模型的get魔术方法
        }
        $user = Auth::guard("api")->user();
        $id = $this->attributes['id'];
        $has_power = PrivacyUserRepository::has_power($this->getMorphClass(),$key,$id,$user->id);//判断是否有权限查看该字段

        if($this->creator_id == $user->id || $has_power){//创建人可以修改有权限的可以修改
            return parent::getAttribute($key);//调用model模型的get魔术方法
        }
        return "privacy";

    }
}