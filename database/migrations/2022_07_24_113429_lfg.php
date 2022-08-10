<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Lfg extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lfg', function (Blueprint $table) {
            $table->uuid('uuid')->primary()->default(DB::raw('(UUID())'));
            $table->string('discord_id')->nullable();
            $table->string('owner');
            $table->string('title');
            $table->string('description');
            $table->tinyInteger('group_size');
            $table->timestamp('time_of_start')->nullable();
            $table->enum('type', ['gambit', 'raid', 'pve', 'pvp', 'other']);
            $table->boolean('manual')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('lfg');
    }
}
