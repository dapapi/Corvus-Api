<?php
/**
 * Created by PhpStorm.
 * User: xiao
 * Date: 2018/10/24
 * Time: 下午4:05
 */
namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait CustomizeFilter
{
    // todo 自定义筛选公共代码段
    public function filter(Builder $builder, array $filterArr)
    {

    }
}