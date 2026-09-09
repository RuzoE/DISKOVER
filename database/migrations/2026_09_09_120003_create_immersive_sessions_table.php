<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('immersive_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('immersive_experience_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('launch_token', 64)->unique();
            $table->string('status', 15)->default('started')->index(); // started | completed | abandoned | expired
            $table->decimal('score', 6, 2)->nullable();
            $table->decimal('max_score', 6, 2)->nullable();
            $table->json('payload')->nullable();                       // telemetría/resultados recibidos de Unity
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['immersive_experience_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('immersive_sessions');
    }
};
