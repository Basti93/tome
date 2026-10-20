<?php

namespace App;

use App\Mail\InvitePassword;
use App\Mail\ResetPassword;
use Hash;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject
{
    /** Notification types that are OFF unless the user switched them on. */
    public const OPT_IN_NOTIFICATION_TYPES = ['trainer_upcoming', 'trainer_evaluation_reminder_email'];

    use HasRoles;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'firstName', 'familyName', 'email', 'password', 'group_id', 'birthdate', 'notification_preferences',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'updated_at', 'created_at', 'roles', 'email_verification_token',
    ];

    protected $casts = [
        'locked_until' => 'datetime',
        'email_verified_at' => 'datetime',
        'notification_preferences' => 'array',
    ];

    protected $appends = ['roleNames', 'group_ids', 'trainer_branch_ids', 'name'];

    public function getNameAttribute()
    {
        return $this->firstName.' '.$this->familyName;
    }

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

    /**
     * Check if a notification type is enabled for this user.
     *
     * @param  string  $type
     * @return bool
     */
    public function isNotificationEnabled($type)
    {
        $preferences = $this->notification_preferences ?? [];

        // keys missing in older stored preferences count as enabled, except the opt-in types
        return $preferences[$type] ?? ! in_array($type, self::OPT_IN_NOTIFICATION_TYPES, true);
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

    /** @return HasMany<NotificationToken, $this> */
    public function notificationTokens(): HasMany
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

    /**
     * Frontend link (hash router) on which a user sets a new password with a reset token.
     */
    public function passwordResetUrl(string $token): string
    {
        return config('app.vue_url', 'http://localhost:8080').'/#/reset-password?token='.$token.'&email='.urlencode($this->email);
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        Mail::to($this->email)->send(new ResetPassword($this, $this->passwordResetUrl($token)));
    }

    /**
     * Send the invitation mail ("set your password") that uses the password reset token and link.
     */
    public function sendInvitation(string $token): void
    {
        Mail::to($this->email)->send(new InvitePassword($this, $this->passwordResetUrl($token)));
    }
}
