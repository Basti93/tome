<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
  protected $hidden = ['updated_at', 'created_at'];

  public function branch()
  {
    return $this->belongsTo('App\Branch','branches', 'branch_id', 'id');
  }


}
