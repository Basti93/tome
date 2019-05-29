<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MigrateAccountingTime extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $trainings = \App\Training::with('trainers')->get();
        echo('Found '.$trainings->count().' Trainings');
        foreach ($trainings as $training) {
            $trainers = \App\TrainingTrainer::where('training_id', $training->id)->get();
            echo('Found '.$trainers->count().' Trainers');
            foreach ($trainers as $trainer) {
                echo('test test');
                echo('Trainer '.$trainer->accounting_time_start);
                if ($trainer->accounting_time_start == null && $trainer->accounting_time_end == null) {
                    $trainer->accounting_time_start = $training->start;
                    $trainer->accounting_time_end = $training->end;
                    $trainer->save();
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
