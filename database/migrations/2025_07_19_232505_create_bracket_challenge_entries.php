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
        Schema::create('bracket_challenge_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bracket_challenge_id')->constrained('bracket_challenges')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name');  
            $table->string('status', 20)->default('active'); // 'active', 'eliminated', 'won'
            $table->unsignedTinyInteger('correct_predictions_count')->default(0);
            $table->string('slug')->unique();
            $table->timestamps();

            $table->unique(['user_id', 'bracket_challenge_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bracket_challenge_entries');
    }
};
