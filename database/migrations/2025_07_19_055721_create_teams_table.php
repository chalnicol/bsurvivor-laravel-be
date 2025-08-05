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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->nullable()->constrained()->onDelete("cascade");
            $table->string("fname");
            $table->string("lname");
            $table->string("abbr");
            $table->string("logo")->nullable();    
            $table->string("conference")->nullable();   
            $table->string("slug")->unique(); 
            $table->timestamps();
            $table->unique(['abbr', 'league_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
