<?php

namespace Database\Factories;

use App\Models\Career;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Career> */
class CareerFactory extends Factory
{
    protected $model = Career::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->jobTitle();

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'location' => 'Monrovia',
            'employment_type' => 'full_time',
            'is_active' => true,
        ];
    }
}
