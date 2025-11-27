<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('places', function (Blueprint $table) {
            $table->id();
            $table->string('place_name');
            $table->text('description'); // Changed from caption to description
            $table->json('images'); // Still supporting multiple images
            $table->text('google_map_link');
            $table->double('latitude', 10, 6)->nullable();
            $table->double('longitude', 10, 6)->nullable();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_merged')->default(false); // For admin duplicate merging
            $table->json('merged_from_ids')->nullable(); // Track which places were merged into this one
            $table->boolean('is_verified')->default(false); // For admin place verification
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('places');
    }
};
