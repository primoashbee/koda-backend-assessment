<?php

declare(strict_types=1);

use Domain\Projects\DTO\ProjectDTO;
use Domain\Projects\Enums\ProjectPriorityEnum;
use Domain\Projects\Enums\ProjectStatusEnum;

pest()->group('projects', 'domain');

it('maps every provided field to its database column', function () {
    $projectDTO = new ProjectDTO(
        clientName: 'Acme Corp',
        projectName: 'Website Redesign',
        description: 'Rebuild the marketing site.',
        status: ProjectStatusEnum::InProgress,
        priority: ProjectPriorityEnum::High,
        startDate: '2026-10-01',
        dueDate: '2026-12-15',
    );

    $attributes = $projectDTO->toAttributes();

    expect($attributes)->toBe([
        'client_name' => 'Acme Corp',
        'project_name' => 'Website Redesign',
        'description' => 'Rebuild the marketing site.',
        'start_date' => '2026-10-01',
        'due_date' => '2026-12-15',
        'status' => ProjectStatusEnum::InProgress,
        'priority' => ProjectPriorityEnum::High,
    ]);
});

it('leaves out a status and priority that were not provided so the current values are kept', function () {
    $projectDTO = new ProjectDTO(
        clientName: 'Acme Corp',
        projectName: 'Website Redesign',
        description: null,
        status: null,
        priority: null,
        startDate: null,
        dueDate: null,
    );

    $attributes = $projectDTO->toAttributes();

    expect($attributes)->toBe([
        'client_name' => 'Acme Corp',
        'project_name' => 'Website Redesign',
        'description' => null,
        'start_date' => null,
        'due_date' => null,
    ]);
});
