<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTrainingsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trainings', function (Blueprint $table) {
            $table->increments('id');
            $table->dateTime('start');
            $table->dateTime('end');
            $table->text('comment')->nullable();
            $table->unsignedInteger('location_id')->nullable();
            $table->foreign('location_id')->references('id')->on('locations');
            $table->unsignedInteger('training_series_id')->nullable();
            $table->foreign('training_series_id')->references('id')->on('training_series');
            $table->timestamps();
        });

      Schema::create('training_trainer', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('training_id')->nullable();
        $table->foreign('training_id')->references('id')->on('trainings');
        $table->unsignedInteger('user_id')->nullable();
        $table->foreign('user_id')->references('id')->on('users');
      });

      Schema::create('training_participation', function (Blueprint $table) {
        $table->increments('id');
        $table->boolean('attend')->nullable();
        $table->text('cancelreason')->nullable();
        $table->unsignedInteger('training_id')->nullable();
        $table->foreign('training_id')->references('id')->on('trainings');
        $table->unsignedInteger('user_id')->nullable();
        $table->foreign('user_id')->references('id')->on('users');
      });

      Schema::create('training_group', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('training_id')->nullable();
        $table->foreign('training_id')->references('id')->on('trainings');
        $table->unsignedInteger('group_id')->nullable();
        $table->foreign('group_id')->references('id')->on('groups');
      });

      Schema::create('training_content', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('training_id')->nullable();
        $table->foreign('training_id')->references('id')->on('trainings');
        $table->unsignedInteger('content_id')->nullable();
        $table->foreign('content_id')->references('id')->on('contents');
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('training_participation');
        Schema::dropIfExists('training_group');
        Schema::dropIfExists('training_trainer');
        Schema::dropIfExists('training_content');
        Schema::dropIfExists('trainings');
    }
}
