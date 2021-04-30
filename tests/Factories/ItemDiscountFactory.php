<?php

namespace Xtwoend\Wallet\Test\Factories;

use Xtwoend\Wallet\Test\Models\ItemDiscount;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemDiscountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ItemDiscount::class;

    /**
     * Define the model's default state.
     *
     * @return array
     * @throws
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->domainName,
            'price' => random_int(200, 700),
            'quantity' => random_int(10, 100),
        ];
    }
}
