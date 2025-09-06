<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Eliminar la tabla existente y recrearla con la estructura correcta
        Schema::dropIfExists('user_institucione');
        
        Schema::create('user_institucione', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('institucion_id')->constrained('institucione')->onDelete('cascade');
            $table->timestamps();
            
            // Evitar duplicados
            $table->unique(['user_id', 'institucion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_institucione');
        
        // Recrear la tabla original si es necesario
        Schema::create('user_institucione', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('institucione_id')->constrained('institucione')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['user_id', 'institucione_id']);
        });
    }
};