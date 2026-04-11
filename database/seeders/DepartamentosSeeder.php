<?php

namespace BaironBernal\ColombiaLocations\Database\Seeders;

use BaironBernal\ColombiaLocations\Models\Departamento;
use Illuminate\Database\Seeder;

class DepartamentosSeeder extends Seeder
{
    public function run(): void
    {
        // Datos oficiales del DANE (Departamento Administrativo Nacional de Estadística)
        // Colombia tiene 32 departamentos + Bogotá D.C. como Distrito Capital.
        $departamentos = [
            ['codigo' => '05', 'nombre' => 'Antioquia'],
            ['codigo' => '08', 'nombre' => 'Atlántico'],
            ['codigo' => '11', 'nombre' => 'Bogotá D.C.'],
            ['codigo' => '13', 'nombre' => 'Bolívar'],
            ['codigo' => '15', 'nombre' => 'Boyacá'],
            ['codigo' => '17', 'nombre' => 'Caldas'],
            ['codigo' => '18', 'nombre' => 'Caquetá'],
            ['codigo' => '19', 'nombre' => 'Cauca'],
            ['codigo' => '20', 'nombre' => 'Cesar'],
            ['codigo' => '23', 'nombre' => 'Córdoba'],
            ['codigo' => '25', 'nombre' => 'Cundinamarca'],
            ['codigo' => '27', 'nombre' => 'Chocó'],
            ['codigo' => '41', 'nombre' => 'Huila'],
            ['codigo' => '44', 'nombre' => 'La Guajira'],
            ['codigo' => '47', 'nombre' => 'Magdalena'],
            ['codigo' => '50', 'nombre' => 'Meta'],
            ['codigo' => '52', 'nombre' => 'Nariño'],
            ['codigo' => '54', 'nombre' => 'Norte de Santander'],
            ['codigo' => '63', 'nombre' => 'Quindío'],
            ['codigo' => '66', 'nombre' => 'Risaralda'],
            ['codigo' => '68', 'nombre' => 'Santander'],
            ['codigo' => '70', 'nombre' => 'Sucre'],
            ['codigo' => '73', 'nombre' => 'Tolima'],
            ['codigo' => '76', 'nombre' => 'Valle del Cauca'],
            ['codigo' => '81', 'nombre' => 'Arauca'],
            ['codigo' => '85', 'nombre' => 'Casanare'],
            ['codigo' => '86', 'nombre' => 'Putumayo'],
            ['codigo' => '88', 'nombre' => 'San Andrés, Providencia y Santa Catalina'],
            ['codigo' => '91', 'nombre' => 'Amazonas'],
            ['codigo' => '94', 'nombre' => 'Guainía'],
            ['codigo' => '95', 'nombre' => 'Guaviare'],
            ['codigo' => '97', 'nombre' => 'Vaupés'],
            ['codigo' => '99', 'nombre' => 'Vichada'],
        ];

        // insert() inserta todos los registros de una sola vez (más eficiente
        // que hacer un create() por cada uno en un loop).
        Departamento::insert($departamentos);
    }
}
