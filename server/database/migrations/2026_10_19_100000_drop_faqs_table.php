<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The FAQ feature was removed (the info page is static now); its old entries are dropped with the table.
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('faqs');
    }

    public function down(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->increments('id');
            $table->text('headline')->nullable();
            $table->text('content')->nullable();
            $table->timestamps();
        });
    }
};
