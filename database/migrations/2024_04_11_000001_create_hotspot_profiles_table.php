<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHotspotProfilesTable extends Migration
{
    public function up()
    {
        Schema::create('hotspot_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('mikrotik_id')->nullable();
            $table->foreignId('router_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('rate_limit')->nullable();
            $table->decimal('price', 10, 2)->nullable()->default(0.00);
            $table->string('shared_users')->nullable();
            $table->string('session_timeout')->nullable();
            $table->string('mac_cookie_timeout')->nullable();
            $table->string('keepalive_timeout')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hotspot_profiles');
    }
}