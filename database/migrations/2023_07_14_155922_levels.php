<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Levels extends Migration
{
    public function up(): void
    {
        Schema::create('levels', function (Blueprint $table) {
            $table->id();
            $table->string('guild_id');
            $table->string('user_id');
            $table->tinyInteger('level')->unsigned();
            $table->integer('xp_current')->unsigned();
            $table->integer('xp_total')->unsigned();
            $table->integer('messages')->unsigned();
            $table->timestamp('suspended')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        //
    }
}
