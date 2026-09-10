<?php

declare(strict_types=1);

namespace Domain\Projects\Database\Factories;

use Domain\Projects\Enums\ProjectPriorityEnum;
use Domain\Projects\Enums\ProjectStatusEnum;
use Domain\Projects\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Project>
     */
    protected $model = Project::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 month', '+1 month');

        return [
            'client_name' => fake()->company(),
            'project_name' => rtrim(fake()->sentence(3), '.'),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(ProjectStatusEnum::cases()),
            'priority' => fake()->randomElement(ProjectPriorityEnum::cases()),
            'start_date' => $startDate,
            'due_date' => fake()->dateTimeBetween($startDate, '+1 year'),
        ];
    }
}
