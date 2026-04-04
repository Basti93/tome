<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AuthLog extends Model
{
    protected $fillable = ['user_id', 'action', 'ip_address', 'user_agent', 'details'];
    protected $casts = ['details' => 'json'];
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
