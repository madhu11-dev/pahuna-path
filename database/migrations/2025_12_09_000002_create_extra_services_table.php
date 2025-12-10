<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extra_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accommodation_id')->constrained()->onDelete('cascade');
            $table->string('service_name');
            $table->decimal('price', 10, 2);
            $table->string('unit')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('accommodation_id');
            $table->index('is_active');
        });
    }
}