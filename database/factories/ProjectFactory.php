<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Project> */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->catchPhrase();

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'status' => 'ongoing',
            'is_active' => true,
        ];
    }
}
