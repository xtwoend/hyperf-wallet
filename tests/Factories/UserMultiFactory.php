<?php

namespace Xtwoend\Wallet\Test\Factories;

use Xtwoend\Wallet\Test\Models\UserMulti;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserMultiFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserMulti::class;

    /**
     * Define the model's default state.
     *
     * @return array
     * @throws
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
        ];
    }
}
