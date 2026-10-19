<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Training extends Model
{
    //
    protected $appends = ['group_ids', 'trainer_ids', 'content_ids'];

    protected $hidden = ['updated_at', 'created_at'];

    public function participants()
    {
        return $this->belongsToMany(User::class, 'training_participation', 'training_id', 'user_id')->withPivot('attend');
    }

    public function trainingParticipation()
    {
        return $this->hasMany(TrainingParticipation::class, 'training_id');
    }

    /** @return BelongsToMany<User, $this> */
    public function trainers(): BelongsToMany
    {
        return $this->belongsToMany('App\User', 'training_trainer', 'training_id', 'user_id');
    }

    public function trainerEvaluations()
    {
        return $this->hasMany('App\TrainingTrainer');
    }

    /** @return BelongsToMany<Group, $this> */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany('App\Group', 'training_group', 'training_id', 'group_id');
    }

    public function contents()
    {
        return $this->belongsToMany('App\Content', 'training_content', 'training_id', 'content_id');
    }

    public function trainingSeries()
    {
        return $this->hasOne('App\TrainingSeries');
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
