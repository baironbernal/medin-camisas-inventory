<?php

namespace Database\Seeders;

use App\Models\Season;
use Illuminate\Database\Seeder;

class SeasonSeeder extends Seeder
{
    public function run(): void
    {
        $seasons = [
            [
                'code' => 'SUMMER-2024',
                'name' => 'Verano 2024',
                'description' => 'Colección de verano con tejidos frescos y colores vibrantes',
                'start_date' => '2023-12-01',
                'end_date' => '2024-03-31',
                'is_active' => true,
                'metadata' => [
                    'target_audience' => 'Jóvenes y adultos',
                    'theme' => 'Tropical Paradise',
                    'notes' => 'Enfoque en materiales ligeros y transpirables',
                ],
            ],
            [
                'code' => 'FALL-2024',
                'name' => 'Otoño 2024',
                'description' => 'Colección de otoño con tonos tierra y tejidos cálidos',
                'start_date' => '2024-04-01',
                'end_date' => '2024-06-30',
                'is_active' => true,
                'metadata' => [
                    'target_audience' => 'Todos los públicos',
                    'theme' => 'Urban Comfort',
                    'notes' => 'Transición entre temporadas',
                ],
            ],
            [
                'code' => 'WINTER-2024',
                'name' => 'Invierno 2024',
                'description' => 'Colección de invierno con prendas abrigadas',
                'start_date' => '2024-07-01',
                'end_date' => '2024-09-30',
                'is_active' => true,
                'metadata' => [
                    'target_audience' => 'Familias',
                    'theme' => 'Cozy Winter',
                    'notes' => 'Materiales térmicos y diseños clásicos',
                ],
            ],
            [
                'code' => 'SPRING-2024',
                'name' => 'Primavera 2024',
                'description' => 'Colección de primavera con diseños florales',
                'start_date' => '2024-10-01',
                'end_date' => '2024-11-30',
                'is_active' => false,
                'metadata' => [
                    'target_audience' => 'Mujeres y niños',
                    'theme' => 'Bloom',
                    'notes' => 'Colores pasteles y estampados florales',
                ],
            ],
        ];

        foreach ($seasons as $season) {
            Season::create($season);
        }
    }
}


