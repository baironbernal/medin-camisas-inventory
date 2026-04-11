<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * up() se ejecuta cuando corres: php artisan migrate
     * Aquí CREAMOS la tabla.
     */
    public function up(): void
    {
        Schema::create('departamentos', function (Blueprint $table) {
            // id() crea una columna "id" autoincremental, llave primaria.
            $table->id();

            // El código DANE es el identificador oficial del DANE (Departamento
            // Administrativo Nacional de Estadística) para cada departamento.
            // Ej: "05" = Antioquia, "11" = Bogotá D.C., "76" = Valle del Cauca
            $table->string('codigo', 2)->unique();

            // Nombre oficial del departamento.
            $table->string('nombre', 100);
        });
    }

    /**
     * down() se ejecuta cuando corres: php artisan migrate:rollback
     * Aquí DESHACEMOS lo que hicimos en up(). Siempre debe ser el inverso.
     */
    public function down(): void
    {
        Schema::dropIfExists('departamentos');
    }
};
