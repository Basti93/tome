<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('firstName');
            $table->string('familyName');
            $table->date('birthdate')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('password')->nullable();
            $table->boolean('active')->comment('If the user is active (delete like action)')->default(1);
            $table->boolean('approved')->comment('Self registered users first need to be a approved')->default(0);
            $table->boolean('registered')->comment('Users that are not registered (with email and password)')->default(1);
            $table->rememberToken();
            $table->timestamps();
        });

      Schema::create('user_group', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('group_id')->nullable();
        $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
        $table->unsignedInteger('user_id')->nullable();
        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
      });

      Schema::create('trainer_group', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('group_id')->nullable();
        $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
        $table->unsignedInteger('user_id')->nullable();
        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
      });

      Schema::create('user_notification_token', function (Blueprint $table) {
        $table->increments('id');
        $table->string('token')->unique();
        $table->unsignedInteger('user_id')->nullable();
        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
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
        Schema::dropIfExists('user_notification_token');
        Schema::dropIfExists('user_group');
        Schema::dropIfExists('trainer_group');
        Schema::dropIfExists('users');
    }
}
