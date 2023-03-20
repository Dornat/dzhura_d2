<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Settings extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('guild_id');
            $table->longText('object');
            $table->string('created_by');
            $table->string('updated_by');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::drop('settings');
    }
}
