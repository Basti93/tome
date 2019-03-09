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
            $table->boolean('floor')->default(false);
            $table->boolean('pommelhorse')->default(false);
            $table->boolean('stillrings')->default(false);
            $table->boolean('vault')->default(false);
            $table->boolean('parallelbars')->default(false);
            $table->boolean('horizontalbar')->default(false);
            $table->boolean('unevenbars')->default(false);
            $table->boolean('balancebeam')->default(false);
            $table->boolean('strength')->default(false);
            $table->boolean('flexibility')->default(false);
            $table->boolean('play')->default(false);
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

      Schema::create('contents', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name');
        $table->text('svg_icon')->nullable();
        $table->unsignedInteger('branch_id')->nullable();
        $table->foreign('branch_id')->references('id')->on('branches');
        $table->unsignedInteger('order')->nullable();
        $table->timestamps();
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
        Schema::dropIfExists('contents');
        Schema::dropIfExists('trainings');
    }
}
