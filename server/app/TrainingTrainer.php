<?php

namespace App;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TrainingTrainer extends Pivot
{
    protected $appends = ['accountingHours', 'accountingMinutes'];
    protected $hidden = ['id'];
    public $timestamps = false;
    protected $dates = [
        'accounting_time_start',
        'accounting_time_end',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function getAccountingHoursAttribute() {
        if (!empty($this->accounting_time_start) && !empty($this->accounting_time_end)) {
            return $this->getAccountingMinutesAttribute() / 45;
        }
    }

    public function getAccountingMinutesAttribute() {
        if (!empty($this->accounting_time_start) && !empty($this->accounting_time_end)) {
            $diff = $this->accounting_time_end->diff($this->accounting_time_start);
            $minutes = $diff->days * 24 * 60;
            $minutes += $diff->h * 60;
            $minutes += $diff->i;
            return $minutes;
        }
    }
}
