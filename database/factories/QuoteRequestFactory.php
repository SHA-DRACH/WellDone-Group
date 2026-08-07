<?php

namespace Database\Factories;

use App\Models\QuoteRequest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<QuoteRequest> */
class QuoteRequestFactory extends Factory
{
    protected $model = QuoteRequest::class;

    public function definition(): array
    {
        return [
            'reference' => 'WDG-Q-'.Str::upper(Str::random(8)),
            'contact_name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => '+231770'.$this->faker->numerify('######'),
            'project_title' => $this->faker->unique()->catchPhrase(),
            'description' => $this->faker->paragraph(),
            'status' => 'new',
            'priority' => 'normal',
        ];
    }
}
