<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContentsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

      Schema::create('contents', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name');
        $table->text('svg_icon')->nullable();
        $table->unsignedInteger('branch_id')->nullable();
        $table->foreign('branch_id')->references('id')->on('branches');
        $table->unsignedInteger('order')->nullable();
        $table->timestamps();
      });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contents');
    }
}
