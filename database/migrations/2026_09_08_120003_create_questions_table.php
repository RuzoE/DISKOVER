<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20)->default('single'); // single | multiple | boolean | open
            $table->text('statement');
            $table->decimal('score', 5, 2)->default(1);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['evaluation_id', 'position']);
        });

        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('text', 500);
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['question_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('questions');
    }
};
