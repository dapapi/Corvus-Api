<?php

namespace App\Repositories;



use App\Models\FilterField;
use http\Env\Request;
use phpDocumentor\Reflection\Types\Null_;

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

          $field = $v['field'];
          $operator = $v['operator'];
          $value = $v['value'];
          $type = $v['type'];
          if(!empty($v['id'])){
              $id = hashid_decode($v['id']);
          }else{
              $id = Null;
          }
          $relation_contidion = FilterField::where('id',$id)->value('relate_contion');//查找附加搜索条件
          if($field == 'operate_logs.created_at' && $type == '2')
          {

              unset($payload['conditions'][$k]);
          }

          if ($field){
              switch ($v['operator']) {
                  case 'LIKE':
                  case 'like':
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

          if (!$field && $relation_contidion){
              $relation_contidion = $relation_contidion;
              $relation_contidion = str_replace('{operator}',$operator,$relation_contidion);
              $relation_contidion = str_replace('{value}',$value,$relation_contidion);
              $query->whereRaw($relation_contidion);
          }

          if ($field && $relation_contidion){
              $query->whereRaw($relation_contidion);
          }
      }
      return $query;
    }

    //为原生sql生成查询条件
    public static function getCondition($payload)
    {
        $where = "";
        $placeholder = [];
        foreach($payload['conditions'] as $k => $v) {

            $field = $v['field'];
            $operator = $v['operator'];
            $value = $v['value'];
            $type = $v['type'];
            if (!empty($v['id'])) {
                $id = hashid_decode($v['id']);
            } else {
                $id = Null;
            }
            if ($field){
                switch ($v['operator']) {
                    case 'LIKE':
                    case 'like':
                        $value = '%' . $v['value'] . '%';
                        $where .= " and {$field} like :{$field}";
                        break;
                    case 'in':
                        if ($type >= 5)#type = 5 type =6
                            foreach ($value as &$v) {
                                $v = hashid_decode($v);
                            }
                        unset($v);
                        $value = implode($value,",");
                        $where .= " and {$field} in :{$field}";
                        break;
                    case '>':
                        $where .= " and {$field} > :{$field}";
                        break;
                    case '>=':
                        $where .= " and {$field} >= :{$field}";
                        break;
                    case '<':
                        $where .= " and {$field} < :{$field}";
                        break;
                    case '<=':
                        $where .= " and {$field} <= :{$field}";
                        break;

                    default:
                        $where .= " and {$field} {$operator} :{$field}";
                        break;
                }
                $placeholder[":{$field}"] = $value;
            }

        }
        return ["where"=>$where,'placeholder'=>$placeholder];
    }

}
