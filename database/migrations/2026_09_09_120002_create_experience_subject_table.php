<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('experience_subject', function (Blueprint $table) {
            $table->foreignId('immersive_experience_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_required')->default(false);

            $table->primary(['immersive_experience_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experience_subject');
    }
};
