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
            $table->foreignId('league_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_public')->default(false); // Whether the challenge is public or private
            $table->json('bracket_data')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('slug')->unique();
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
