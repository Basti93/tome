<?php

namespace App;

use Hash;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable implements JWTSubject
{
    use Notifiable;
    use HasRoles;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'firstName', 'familyName', 'email', 'password', 'approved', 'group_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'updated_at', 'created_at', 'pivot', 'roles'
    ];

    protected $appends = ['roleNames', 'trainer_group_ids'];

    /**
     * Automatically creates hash for the user password.
     *
     * @param  string  $value
     * @return void
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }


  public function group()
  {
    return $this->hasOne('App\Group', 'id', 'group_id');
  }

  public function trainerGroups()
  {
    return $this->belongsToMany('App\Group', 'trainer_group', 'user_id');
  }

  public function getTrainerGroupIdsAttribute()
  {
    return $this->trainerGroups->pluck('pivot.group_id');
  }

  public function getRoleNamesAttribute()
  {
    return $this->getRoleNames();
  }

  public function isApproved() {
      return $this->approved == 1;
  }

}
