<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('immersive_experiences', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->string('provider', 30)->default('simulator'); // unity_webgl | unity_standalone | webxr | external | simulator
            $table->string('launch_url', 2048)->nullable();
            $table->json('config')->nullable();                    // parámetros para el cliente (escena, dificultad…)
            $table->decimal('max_score', 6, 2)->default(100);
            $table->string('status', 20)->default('draft')->index();
            $table->foreignId('activity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('immersive_experiences');
    }
};
