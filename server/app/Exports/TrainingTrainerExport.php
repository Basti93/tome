<?php

namespace App\Exports;

use App\Location;
use App\Training;
use App\TrainingTrainer;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TrainingTrainerExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    protected $id;

    public function __construct($user, $from, $to)
    {
        $this->user = $user;
        $this->from = $from;
        $this->to = $to;
    }

    public function headings(): array
    {
        return ['Trainer', 'Ort', 'Datum', 'Beginn', 'Ende', 'ÜL-Stunden (45 min)', 'Minuten Gesamt'];
    }

    /**
     * @var TrainingTrainer
     */
    public function map($trainingTrainer): array
    {
        $training = Training::whereId($trainingTrainer->training_id)->first();
        $location = Location::whereId($training->location_id)->first();

        return [
            $this->user->firstName.' '.$this->user->familyName,
            $location === null ? '' : $location->name,
            $trainingTrainer->accounting_time_start->format('d.m.Y'),
            $trainingTrainer->accounting_time_start->format('H:i'),
            $trainingTrainer->accounting_time_end->format('H:i'),
            round($trainingTrainer->accountingHours, 2),
            $trainingTrainer->accountingMinutes,
        ];
    }

    /**
     * @return Collection
     */
    public function collection(): Enumerable
    {
        return TrainingTrainer::where('user_id', $this->user->id)
            ->whereBetween('accounting_time_start', [$this->from, $this->to])
            ->get();
    }
}
