<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bracket_challenge', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->string('description')->nullable();
            $table->json('bracket_data')->nullable(); // Store bracket data in JSON format
            $table->boolean('is_public')->default(false); // Whether the challenge is public or private
            $table->date('start_date');
            $table->date('end_date');
            $table->string('slug');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bracket_challenge');
    }
};
