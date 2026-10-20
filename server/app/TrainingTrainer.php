<?php

namespace App;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

class TrainingTrainer extends Pivot
{
    protected $appends = ['accountingHours', 'accountingMinutes'];

    protected $hidden = ['id'];

    public $timestamps = false;

    protected $casts = [
        'accounting_time_start' => 'datetime',
        'accounting_time_end' => 'datetime',
    ];

    /**
     * Local time with offset like Training start/end ("2099-01-10T18:15:00+01:00"). The default (UTC "Z") would
     * be shown two hours early because the app reads API times as local time and ignores the offset.
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return Carbon::instance($date)->setTimezone(config('app.timezone'))->format(\DATE_ATOM);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function training()
    {
        return $this->belongsTo(Training::class)->withTrashed();
    }

    public function getAccountingHoursAttribute()
    {
        if (! empty($this->accounting_time_start) && ! empty($this->accounting_time_end)) {
            return $this->getAccountingMinutesAttribute() / 45;
        }
    }

    public function getAccountingMinutesAttribute()
    {
        if (! empty($this->accounting_time_start) && ! empty($this->accounting_time_end)) {
            $diff = $this->accounting_time_end->diff($this->accounting_time_start);
            $minutes = $diff->days * 24 * 60;
            $minutes += $diff->h * 60;
            $minutes += $diff->i;

            return $minutes;
        }
    }
}
