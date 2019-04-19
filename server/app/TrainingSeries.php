<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TrainingSeries extends Model
{
    //
  protected $appends = ['group_ids','trainer_ids', 'content_ids'];
  protected $hidden = ['updated_at', 'created_at'];

  public function trainers()
  {
    return $this->belongsToMany('App\User','training_series_trainer', 'training_id', 'user_id');
  }

  public function groups()
  {
    return $this->belongsToMany('App\Group','training_series_group', 'training_id', 'group_id');
  }

  public function contents()
  {
    return $this->belongsToMany('App\Content','training_series_content', 'training_id', 'content_id');
  }

  public function getGroupIdsAttribute()
  {
    return $this->groups->pluck('pivot.group_id');
  }

  public function getTrainerIdsAttribute()
  {
    return $this->trainers->pluck('pivot.user_id');
  }

  public function getContentIdsAttribute()
  {
    return $this->contents->pluck('pivot.content_id');
  }

}
