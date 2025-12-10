<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accommodation_id')->constrained()->onDelete('cascade');
            $table->string('room_name');
            $table->enum('room_type', ['single', 'double', 'suite', 'family', 'dormitory']);
            $table->boolean('has_ac')->default(false);
            $table->integer('capacity');
            $table->integer('total_rooms');
            $table->decimal('base_price', 10, 2);
            $table->text('description')->nullable();
            $table->json('images')->nullable();
            $table->timestamps();
            
            $table->index('accommodation_id');
            $table->index(['accommodation_id', 'room_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
