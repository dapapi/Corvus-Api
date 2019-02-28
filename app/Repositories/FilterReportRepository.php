<?php

namespace App\Repositories;



use App\Models\FilterField;

class FilterReportRepository
{

    public static function getTableNameAndCondition($payload,$query)
    {
           //  $array = [];//查询条件
            //
      if(empty($payload['conditions'])){
         return null;
      }
      foreach($payload['conditions'] as $k => $v){
         // dd($payload['conditions']);
          $field = $v['field'];
          $operator = $v['operator'];
          $value = $v['value'];
          $type = $v['type'];
          $id = hashid_decode($v['id']);
          $relation_contidion = FilterField::where('id',$id)->pluck('relate_contion')[0];//查找附加搜索条件

          $id = hashid_decode($v['id']);

          $relation_contidion = FilterField::where('id',$id)->pluck('relate_contion')->toArray();//查找附加搜索条件
          if ($field){
              switch ($v['operator']) {
                  case 'LIKE':
                      $value = '%' . $v['value'] . '%';
                      $query->whereRaw("$field $operator ?", [$value]);
                      //    $array[]  = [$field,'like','%'.$value.'%'];
                      break;
                  case 'in':
                      if ($type >= 5)
                          foreach ($value as &$v) {
                              $v = hashid_decode($v);
                          }
                      unset($v);
                      $query->whereIn($field, $value);
                      // $array[]  = [$field,'In',$value];
                      break;
                  case '>':

                      //  $query->whereIn($field,'>',$value);
                      $query->where($field,'>',$value);
                      break;
                  case '>=':

                      //  $query->whereIn($field,'>=',$value);
                      $query->where($field,'>=',$value);
                      break;
                  case '<':

                      //  $query->whereIn($field,'<',$value);
                      $query->where($field,'<',$value);
                      break;
                  case '<=':

                      // $query->whereIn($field,'<=',$value);
                      $query->where($field,'<=',$value);
                      break;

                  default:
                      $query->whereRaw("$field $operator ?", [$value]);
                      break;
              }
          }

          if (count($relation_contidion) !== 0){
              $relation_contidion = $relation_contidion[0];
              $relation_contidion = str_replace('{operator}',$operator,$relation_contidion);
              $relation_contidion = str_replace('{value}',$value,$relation_contidion);
              $query->whereRaw($relation_contidion);
          }
          if ($relation_contidion){
              $query->whereRaw($relation_contidion);
          }
        }

      return $query;
    }

}
