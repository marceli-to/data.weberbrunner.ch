<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('title')->nullable();
            $table->string('email')->nullable();
            $table->unsignedSmallInteger('since')->nullable();
            $table->string('role')->nullable();
            $table->string('profile_url')->nullable();
            $table->string('image')->nullable();
            $table->boolean('publish')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
