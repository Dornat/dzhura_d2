<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Lfg extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lfg', function (Blueprint $table) {
            $table->uuid('uuid')->primary()->default(DB::raw('(UUID())'));
            $table->string('discord_id')->nullable();
            $table->string('owner');
            $table->string('title');
            $table->string('description');
            $table->tinyInteger('group_size');
            $table->longText('participants')->nullable();
            $table->longText('reserve')->nullable();
            $table->timestamp('time_of_start')->nullable();
            $table->enum('type', ['gambit', 'raid', 'pve', 'pvp', 'other']);
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
        //
    }
}
