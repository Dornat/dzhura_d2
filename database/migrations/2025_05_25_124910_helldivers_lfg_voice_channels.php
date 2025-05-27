<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class HelldiversLfgVoiceChannels extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hd_lfg_vcs', function (Blueprint $table) {
            $table->id();
            $table->string('guild_id');
            $table->string('vc_discord_id')->comment('VC Discord ID');
            $table->string('lfg_channel_id')->comment('ID of a channel in which Embed is present');
            $table->string('lfg_message_id')->comment('ID of a message with Embed');
            $table->string('owner')->comment('User ID that is the Owner of the VC');
            $table->string('name')->comment('Name of the VC');
            $table->tinyInteger('user_limit')->comment('User limit of the VC');
            $table->string('category')->nullable()->comment('Category Name of the Channel under which the VC will be set');
            $table->longText('participants')->comment('Participants in the lfg in JSON format');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('hd_lfg_vcs');
    }
}
