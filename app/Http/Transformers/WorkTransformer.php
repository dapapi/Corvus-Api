<?php
namespace App\Http\Transformers;

use App\Models\Work;
use League\Fractal\TransformerAbstract;
class WorkTransformer extends TransformerAbstract
{
  protected $availableIncludes = ['creator', 'star'];

  public function transform(Work $work)
  {
    $array = [
      'id'  =>  hashid_encode($work->id),
      'name'  =>  $work->name,
      'director'  =>  $work->director,
      'release_time'  =>  $work->release_time,
      'works_type'  =>  $work->works_type,
      'role'  =>  $work->role,
      'co-star' =>  $work->co-star,
      'created_at' => $star->created_at->toDatetimeString(),
      'updated_at' => $star->updated_at->toDatetimeString(),
      // 'deleted_at' => $star->deleted_at,
    ];
    return $array;
  }

  public function includeCreator(Work $work)
  {
    $user = $work->creator;
    if (!$user)
        return null;
    return $this->item($user, new UserTransformer());
  }
  public function includeStar(Work $work)
  {
    $star = $work->star;
    if(!$star)
      return null;
    return $this->item($star,new StarTransformer());
  }
}
