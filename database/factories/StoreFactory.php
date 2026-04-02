<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class StoreFactory extends Factory
{
    protected $model = Store::class;

    public function definition(): array
    {
        return [
            'code'    => strtoupper($this->faker->unique()->bothify('STR##')),
            'name'    => $this->faker->company(),
            'address' => $this->faker->streetAddress(),
            'city'    => $this->faker->city(),
            'state'   => $this->faker->state(),
            'country' => 'CO',
            'is_active' => true,
        ];
    }
}
