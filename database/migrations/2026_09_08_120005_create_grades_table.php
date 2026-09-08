<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('attempt_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('score', 6, 2);                 // en la escala de activity.max_score
            $table->text('feedback')->nullable();
            $table->string('source', 20)->default('manual'); // manual | auto
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();

            $table->unique(['activity_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
