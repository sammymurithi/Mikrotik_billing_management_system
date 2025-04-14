<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('hotspot_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('rate_limit')->nullable();
            $table->integer('shared_users')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hotspot_profiles');
    }
}; 