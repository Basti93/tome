<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTrainingSeriesTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('training_series', function (Blueprint $table) {
            $table->increments('id');
            $table->time('startTime');
            $table->time('endTime');
            $table->text('weekdays');
            $table->text('comment')->nullable();
            $table->boolean('active')->default(1);
            $table->unsignedInteger('location_id')->nullable();
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('training_series_trainer', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('training_id')->nullable();
            $table->foreign('training_id')->references('id')->on('training_series')->onDelete('cascade');
            $table->unsignedInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('training_series_group', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('training_id')->nullable();
            $table->foreign('training_id')->references('id')->on('training_series')->onDelete('cascade');
            $table->unsignedInteger('group_id')->nullable();
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
        });

        Schema::create('training_series_content', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('training_id')->nullable();
            $table->foreign('training_id')->references('id')->on('training_series')->onDelete('cascade');
            $table->unsignedInteger('content_id')->nullable();
            $table->foreign('content_id')->references('id')->on('contents')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('training_series_group');
        Schema::dropIfExists('training_series_trainer');
        Schema::dropIfExists('training_series_content');
        Schema::dropIfExists('training_series');
    }
}
