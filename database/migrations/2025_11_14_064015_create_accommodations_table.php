<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {

        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis;');

        Schema::create('accommodations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('images')->nullable();
            $table->string('type')->comment('hotel, restaurant, guesthouse, etc.');
            $table->text('description')->nullable();
            $table->decimal('review', 3, 2)->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('place_id')->constrained('places')->onDelete('cascade');
            $table->timestamps();
        });

        //  PostGIS location column
        DB::statement('ALTER TABLE accommodations ADD COLUMN location geography(POINT, 4326);');
    }

    public function down(): void
    {
        Schema::dropIfExists('accommodations');
    }
};
