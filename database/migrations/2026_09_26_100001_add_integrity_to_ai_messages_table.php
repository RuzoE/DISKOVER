<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca de integridad académica de cada mensaje (ADR-0017):
 * null = normal · guide = enviado en modo guiado · block = no se envió al proveedor.
 * Los mensajes bloqueados nunca se reenvían en el historial.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_messages', function (Blueprint $table) {
            $table->string('integrity', 10)->nullable()->after('failed');
        });
    }

    public function down(): void
    {
        Schema::table('ai_messages', function (Blueprint $table) {
            $table->dropColumn('integrity');
        });
    }
};
