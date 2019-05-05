<?php

namespace App;


use Illuminate\Database\Eloquent\Relations\Pivot;

class TrainingParticipation extends Pivot
{


    protected $hidden = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function training()
    {
        return $this->belongsTo(Training::class);
    }


}
