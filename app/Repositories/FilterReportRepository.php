<?php

namespace App\Repositories;



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
      //  return $query;
      return $query;
    }

}
