<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class VoiceChannels extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('voice_channels', function (Blueprint $table) {
            $table->id();
            $table->uuid('lfg_uuid')->nullable();
            $table->foreign('lfg_uuid')->references('uuid')->on('lfg')->onUpdate('cascade')->onDelete('cascade');
            $table->string('guild_id');
            $table->string('vc_discord_id');
            $table->string('owner');
            $table->string('name');
            $table->tinyInteger('user_limit');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('voice_channels');
    }
}
