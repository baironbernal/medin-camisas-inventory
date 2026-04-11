<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('municipios', function (Blueprint $table) {
            $table->id();

            // El código DANE del municipio tiene 5 dígitos:
            // los 2 primeros son el código del departamento + 3 del municipio.
            // Ej: "05001" = Medellín (05=Antioquia, 001=Medellín)
            $table->string('codigo', 5)->unique();

            $table->string('nombre', 150);

            // foreignId crea la columna "departamento_id" como entero sin signo
            // y constrained() agrega la llave foránea que apunta a departamentos.id
            // cascadeOnDelete() significa: si se borra un departamento,
            // se borran automáticamente todos sus municipios.
            $table->foreignId('departamento_id')
                  ->constrained('departamentos')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        // Primero borramos municipios porque depende de departamentos.
        // Si intentáramos borrar departamentos primero, la llave foránea
        // nos lo impediría.
        Schema::dropIfExists('municipios');
    }
};
