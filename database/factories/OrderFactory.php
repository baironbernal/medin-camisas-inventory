<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 50000, 500000);

        return [
            'order_number'        => 'ORD-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6)),
            'status'              => Order::STATUS_PENDING,
            'subtotal_original'   => $subtotal,
            'subtotal_discounted' => $subtotal,
            'subtotal'            => $subtotal,
            'tax'                 => 0,
            'shipping_cost'       => 0,
            'total'               => $subtotal,
            'currency'            => 'COP',
            'customer_email'      => $this->faker->safeEmail(),
            'customer_name'       => $this->faker->name(),
            'customer_phone'      => $this->faker->phoneNumber(),
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => Order::STATUS_PENDING]);
    }

    public function confirmed(): static
    {
        return $this->state(['status' => Order::STATUS_CONFIRMED]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => Order::STATUS_CANCELLED]);
    }

    public function processing(): static
    {
        return $this->state(['status' => Order::STATUS_PROCESSING]);
    }

    public function completed(): static
    {
        return $this->state(['status' => Order::STATUS_COMPLETED]);
    }

    public function forUser(User $user): static
    {
        return $this->state(['user_id' => $user->id]);
    }
}
