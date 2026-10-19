<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AddTrainingContentImage extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contents', function ($table) {
            $table->string('image_name')->nullable();
            $table->dropColumn('svg_icon');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {}
}
