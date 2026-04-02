<?php

namespace Database\Factories;

use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeasonFactory extends Factory
{
    protected $model = Season::class;

    public function definition(): array
    {
        return [
            'code'       => strtoupper($this->faker->unique()->bothify('SS##')),
            'name'       => $this->faker->words(2, true),
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date'   => now()->endOfYear()->toDateString(),
            'is_active'  => true,
        ];
    }
}
