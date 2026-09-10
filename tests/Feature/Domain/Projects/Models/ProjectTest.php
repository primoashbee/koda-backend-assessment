<?php

declare(strict_types=1);

use Domain\Projects\Models\Project;
use Illuminate\Database\UniqueConstraintViolationException;

pest()->group('projects', 'domain');

it('rejects a second project with the same client and project name at the database level', function () {
    Project::factory()->create(['client_name' => 'Acme Corp', 'project_name' => 'Website Redesign']);

    expect(function (): void {
        Project::factory()->create(['client_name' => 'Acme Corp', 'project_name' => 'Website Redesign']);
    })->toThrow(UniqueConstraintViolationException::class);
});
