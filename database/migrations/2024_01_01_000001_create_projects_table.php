<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wp_id')->nullable()->unique();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('year', 4)->nullable();
            $table->string('status')->nullable();
            $table->text('steckbrief')->nullable();
            $table->string('publish_status')->default('publish');
            $table->unsignedInteger('menu_order')->default(0);
            $table->string('color_text_dark', 7)->nullable();
            $table->string('color_text_light', 7)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
