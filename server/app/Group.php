<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
  protected $hidden = ['updated_at', 'created_at'];

  public function branch()
  {
    return $this->hasOne('App\Branch');
  }


}
