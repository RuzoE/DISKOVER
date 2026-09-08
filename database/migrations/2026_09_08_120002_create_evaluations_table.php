<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('time_limit_minutes')->nullable();
            $table->unsignedSmallInteger('max_attempts')->default(1);
            $table->boolean('shuffle_questions')->default(false);
            $table->decimal('pass_score', 5, 2)->nullable(); // porcentaje (0-100)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
