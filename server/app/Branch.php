<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    //
  protected $hidden = ['updated_at', 'created_at', 'pivot'];

}
