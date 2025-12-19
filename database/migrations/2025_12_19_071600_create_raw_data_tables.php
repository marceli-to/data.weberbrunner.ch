<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_data', function (Blueprint $table) {
            $table->id();
            $table->string('number');
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('raw_data_meta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raw_data_id')->constrained('raw_data')->cascadeOnDelete();
            $table->string('label');
            $table->text('value')->nullable();
            $table->integer('position')->default(0);
            $table->timestamps();
        });

        Schema::create('raw_data_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raw_data_id')->constrained('raw_data')->cascadeOnDelete();
            $table->string('group_key')->nullable();
            $table->string('label');
            $table->text('value')->nullable();
            $table->integer('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_data_attributes');
        Schema::dropIfExists('raw_data_meta');
        Schema::dropIfExists('raw_data');
    }
};
