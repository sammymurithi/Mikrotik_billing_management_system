<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('hotspot_users', function (Blueprint $table) {
            $table->id();
            $table->string('mikrotik_id')->nullable();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('mac_address')->nullable();
            $table->string('profile_name')->nullable();
            $table->foreignId('router_id')->constrained('routers')->onDelete('cascade');
            $table->boolean('disabled')->default(false);
            $table->string('status')->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->string('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hotspot_users');
    }
}; 