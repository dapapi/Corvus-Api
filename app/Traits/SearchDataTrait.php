<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2018/12/20
 * Time: 9:57 AM
 */

namespace App\Traits;

use App\Scopes\SearchDataScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * 查询数据范围限制
 * Trait SearchDataTrait
 * @package App\Traits
 */
trait SearchDataTrait
{
    public static function bootSearchData()
    {
        static::addGlobalScope(new SearchDataScope());
    }

}