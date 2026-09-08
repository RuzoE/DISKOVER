<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20)->default('task')->index();   // task | quiz
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->decimal('max_score', 6, 2)->default(100);
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index(['subject_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
