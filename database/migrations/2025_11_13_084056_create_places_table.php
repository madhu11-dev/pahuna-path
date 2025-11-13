<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure PostGIS extension is available
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis;');

        // Create the table without the PostGIS column
        Schema::create('places', function (Blueprint $table) {
            $table->id();
            $table->string('place_name');
            $table->json('images')->nullable();
            $table->string('google_map_link')->nullable();
            $table->text('caption')->nullable();
            $table->decimal('review', 3, 2)->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        // Add the PostGIS location column using raw SQL
        DB::statement('ALTER TABLE places ADD COLUMN location geography(POINT, 4326);');
    }

    public function down(): void
    {
        Schema::dropIfExists('places');
    }
};
