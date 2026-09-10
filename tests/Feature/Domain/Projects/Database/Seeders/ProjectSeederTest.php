<?php

declare(strict_types=1);

use Domain\Projects\Database\Seeders\ProjectSeeder;
use Domain\Projects\Enums\ProjectPriorityEnum;
use Domain\Projects\Enums\ProjectStatusEnum;
use Domain\Projects\Models\Project;

pest()->group('projects', 'domain');

it('seeds the twelve sample projects with their ids', function () {
    $this->seed(ProjectSeeder::class);

    $this->assertDatabaseCount('projects', 12);
    $project = Project::findOrFail(2);
    expect($project->client_name)->toBe('GreenLeaf Cafe')
        ->and($project->project_name)->toBe('Online Ordering System')
        ->and($project->description)->toBe('Develop an online ordering platform for customers.')
        ->and($project->status)->toBe(ProjectStatusEnum::Planning)
        ->and($project->priority)->toBe(ProjectPriorityEnum::Medium)
        ->and($project->start_date?->toDateString())->toBe('2026-06-10')
        ->and($project->due_date?->toDateString())->toBe('2026-08-01');
});

it('updates the sample projects instead of duplicating them when run again', function () {
    $this->seed(ProjectSeeder::class);
    Project::findOrFail(1)->update(['project_name' => 'Renamed Locally']);

    $this->seed(ProjectSeeder::class);

    $this->assertDatabaseCount('projects', 12);
    expect(Project::findOrFail(1)->project_name)->toBe('Corporate Website Redesign');
});

it('restores a soft-deleted sample project when run again', function () {
    $this->seed(ProjectSeeder::class);
    Project::findOrFail(1)->delete();

    $this->seed(ProjectSeeder::class);

    $this->assertDatabaseCount('projects', 12);
    $this->assertNotSoftDeleted(Project::findOrFail(1));
});
