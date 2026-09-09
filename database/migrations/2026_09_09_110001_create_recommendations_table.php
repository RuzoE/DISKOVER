<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30)->index();               // review_content | retry_evaluation | ...
            $table->string('priority', 10)->default('medium');  // high | medium | low
            $table->string('title', 180);
            $table->text('body');
            $table->json('reason')->nullable();                 // métricas que la dispararon
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('content_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 12)->default('pending')->index(); // pending | accepted | dismissed | completed
            $table->string('signature', 80);                    // clave de deduplicación (tipo + objetivo)
            $table->timestamp('generated_at');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'signature']);
            $table->index(['user_id', 'status', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendations');
    }
};
