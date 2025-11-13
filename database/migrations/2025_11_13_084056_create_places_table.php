<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis;');

        Schema::create('places', function (Blueprint $table) {
            $table->id();
            $table->string('place_name');

            // Store multiple images as JSON array
            $table->json('images')->nullable();

            // PostGIS geometry column for location (latitude/longitude)
            $table->point('location', 4326)->nullable();

            $table->string('google_map_link')->nullable();
            $table->text('caption')->nullable();
            $table->decimal('review', 3, 2)->nullable(); // e.g., 4.5

            // Foreign key to users table
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('places');
    }
};
