<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('hotspot_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotspot_user_id')->constrained('hotspot_users')->onDelete('cascade');
            $table->string('mac_address');
            $table->string('ip_address');
            $table->string('uptime');
            $table->string('bytes_in')->nullable();
            $table->string('bytes_out')->nullable();
            $table->string('packets_in')->nullable();
            $table->string('packets_out')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hotspot_sessions');
    }
}; 