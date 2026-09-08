<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('number')->default(1);
            $table->string('status', 20)->default('in_progress'); // in_progress | submitted | graded
            $table->decimal('score', 6, 2)->nullable();          // puntos obtenidos (sobre score total de preguntas)
            $table->decimal('max_score', 6, 2)->nullable();       // suma de score de las preguntas al enviar
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['evaluation_id', 'student_id', 'number']);
            $table->index(['evaluation_id', 'student_id']);
        });

        Schema::create('attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->json('selected_option_ids')->nullable(); // preguntas cerradas
            $table->text('text_answer')->nullable();         // preguntas abiertas
            $table->boolean('is_correct')->nullable();
            $table->decimal('score_awarded', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['attempt_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempt_answers');
        Schema::dropIfExists('attempts');
    }
};
