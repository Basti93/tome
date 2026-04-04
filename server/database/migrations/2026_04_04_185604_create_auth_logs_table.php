<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('auth_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('action'); // login, logout, refresh, signup, email_verified, password_reset_requested, password_reset, login_failed
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('details')->nullable(); // JSON: reason for failure, etc.
            $table->timestamp('created_at')->useCurrent();
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_logs');
    }
};
