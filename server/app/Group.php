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

    public function users()
    {
        return $this->belongsToMany('App\User', 'user_group', 'group_id');
    }

    public function getUserIdsAttribute()
    {
        return $this->users->where('active', 1)->pluck('pivot.user_id');
    }

}
