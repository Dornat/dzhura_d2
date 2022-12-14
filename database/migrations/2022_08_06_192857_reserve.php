<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Reserve extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reserve', function (Blueprint $table) {
            $table->id();
            $table->uuid('lfg_uuid');
            $table->foreign('lfg_uuid')->references('uuid')->on('lfg')->onUpdate('cascade')->onDelete('cascade');
            $table->string('user_id');
            $table->boolean('want_to_go')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('reserve');
    }
}
