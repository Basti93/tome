<?php

namespace App;

use Hash;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;


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
        'firstName', 'familyName', 'email', 'password', 'approved', 'group_id', 'birthdate'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'updated_at', 'created_at', 'roles'
    ];

    protected $appends = ['roleNames', 'group_ids', 'trainer_branch_ids', 'name'];

    public function getNameAttribute() {
        return $this->firstName." ".$this->familyName;
    }

    /**
     * Automatically creates hash for the user password.
     *
     * @param  string $value
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

    public function trainerBranches()
    {
        return $this->belongsToMany('App\Branch', 'trainer_branch', 'user_id');
    }

    public function groups()
    {
        return $this->belongsToMany('App\Group', 'user_group', 'user_id');
    }

    public function notificationTokens()
    {
        return $this->hasMany(NotificationToken::class);
    }

    public function trainings()
    {
        return $this->belongsToMany(Training::class, 'training_participation', 'user_id', 'training_id')->withPivot('attend');
    }

    public function getTrainerBranchIdsAttribute()
    {
        return $this->trainerBranches->pluck('pivot.branch_id');
    }

    public function getGroupIdsAttribute()
    {
        return $this->groups->pluck('pivot.group_id');
    }

    public function getRoleNamesAttribute()
    {
        return $this->getRoleNames();
    }

    public function isApproved()
    {
        return $this->approved == 1;
    }


}
