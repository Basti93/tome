<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tokens of invitation mails (valid 7 days, see config/auth.php); password reset tokens stay in password_resets.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_invites', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_invites');
    }
};
