<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AddAutomaticAttendColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trainings', function ($table) {
            $table->boolean('automatic_attend')->default(1);
        });
        Schema::table('training_series', function ($table) {
            $table->boolean('automatic_attend')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {}
}
