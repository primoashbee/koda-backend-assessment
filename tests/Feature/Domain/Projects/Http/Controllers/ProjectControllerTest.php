<?php

declare(strict_types=1);

use Domain\Projects\Enums\ProjectPriorityEnum;
use Domain\Projects\Enums\ProjectStatusEnum;
use Domain\Projects\Models\Project;
use Domain\Users\Models\User;
use Laravel\Sanctum\Sanctum;

pest()->group('projects', 'domain');

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function projectPayload(array $overrides = []): array
{
    return [
        'clientName' => 'Acme Corp',
        'projectName' => 'Website Redesign',
        'description' => 'Rebuild the marketing site.',
        'status' => 'In Progress',
        'priority' => 'High',
        'startDate' => '2026-10-01',
        'dueDate' => '2026-12-15',
        ...$overrides,
    ];
}

it('returns 401 when no token is provided', function (string $method, string $uri) {
    $response = $this->json($method, $uri, projectPayload());

    $response->assertUnauthorized()
        ->assertExactJson(['message' => 'Unauthenticated.']);
})->with([
    'index' => ['GET', '/api/v1/projects'],
    'show' => ['GET', '/api/v1/projects/1'],
    'store' => ['POST', '/api/v1/projects'],
    'update' => ['PUT', '/api/v1/projects/1'],
    'destroy' => ['DELETE', '/api/v1/projects/1'],
]);

it('returns 401 json when the request does not accept json', function () {
    $response = $this->get('/api/v1/projects');

    $response->assertUnauthorized()
        ->assertExactJson(['message' => 'Unauthenticated.']);
});

it('returns 404 with a message when the project does not exist', function (string $method) {
    Sanctum::actingAs(User::factory()->create());

    $response = $this->json($method, '/api/v1/projects/999', projectPayload());

    $response->assertNotFound()
        ->assertExactJson(['message' => 'Project not found.']);
})->with(['GET', 'PUT', 'DELETE']);

describe('index', function () {
    it('returns paginated projects newest first', function () {
        Sanctum::actingAs(User::factory()->create());
        $olderProject = Project::factory()->create();
        $newerProject = Project::factory()->create();

        $response = $this->getJson(route('api.v1.projects.index'));

        $response->assertOk()
            ->assertJsonPath('message', 'Projects fetched successfully')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $newerProject->id)
            ->assertJsonPath('data.1.id', $olderProject->id)
            ->assertJsonPath('meta.currentPage', 1)
            ->assertJsonPath('meta.total', 2);
    });

    it('does not list soft-deleted projects', function () {
        Sanctum::actingAs(User::factory()->create());
        $activeProject = Project::factory()->create();
        Project::factory()->trashed()->create();

        $response = $this->getJson(route('api.v1.projects.index'));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $activeProject->id);
    });

    it('returns only the projects matching a filter', function (array $filters, array $matchingAttributes, array $otherAttributes) {
        Sanctum::actingAs(User::factory()->create());
        $matchingProject = Project::factory()->create($matchingAttributes);
        Project::factory()->create($otherAttributes);

        $response = $this->getJson(route('api.v1.projects.index', ['filters' => $filters]));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingProject->id);
    })->with([
        'client name' => [['clientName' => 'Acme Corp'], ['client_name' => 'Acme Corp'], ['client_name' => 'Globex']],
        'project name' => [['projectName' => 'CRM Dashboard'], ['project_name' => 'CRM Dashboard'], ['project_name' => 'Mobile App']],
        'status' => [['status' => 'On Hold'], ['status' => ProjectStatusEnum::OnHold], ['status' => ProjectStatusEnum::Completed]],
        'priority' => [['priority' => 'Low'], ['priority' => ProjectPriorityEnum::Low], ['priority' => ProjectPriorityEnum::High]],
    ]);

    it('returns only the projects matching every filter when filters are combined', function () {
        Sanctum::actingAs(User::factory()->create());
        $matchingProject = Project::factory()->create(['status' => ProjectStatusEnum::OnHold, 'priority' => ProjectPriorityEnum::Low]);
        Project::factory()->create(['status' => ProjectStatusEnum::OnHold, 'priority' => ProjectPriorityEnum::High]);
        Project::factory()->create(['status' => ProjectStatusEnum::Completed, 'priority' => ProjectPriorityEnum::Low]);

        $response = $this->getJson(route('api.v1.projects.index', [
            'filters' => ['status' => 'On Hold', 'priority' => 'Low'],
        ]));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingProject->id);
    });

    it('keeps the filters in the pagination links', function () {
        Sanctum::actingAs(User::factory()->create());
        Project::factory()->count(16)->create(['status' => ProjectStatusEnum::Planning]);

        $response = $this->getJson(route('api.v1.projects.index', ['filters' => ['status' => 'Planning']]));

        $response->assertOk()
            ->assertJsonPath('meta.total', 16);
        expect(urldecode((string) $response->json('links.next')))->toContain('filters[status]=Planning');
    });

    it('sorts projects by the given field and direction', function (string $sortOrder, array $expectedClientNames) {
        Sanctum::actingAs(User::factory()->create());
        Project::factory()->create(['client_name' => 'Bravo']);
        Project::factory()->create(['client_name' => 'Alpha']);
        Project::factory()->create(['client_name' => 'Charlie']);

        $response = $this->getJson(route('api.v1.projects.index', [
            'sortBy' => 'clientName',
            'sortOrder' => $sortOrder,
        ]));

        $response->assertOk();
        expect(array_column($response->json('data'), 'clientName'))->toBe($expectedClientNames);
    })->with([
        'ascending' => ['asc', ['Alpha', 'Bravo', 'Charlie']],
        'descending' => ['desc', ['Charlie', 'Bravo', 'Alpha']],
    ]);

    it('orders projects sharing the sorted value by id in the same direction', function (string $sortOrder, array $expectedPositions) {
        Sanctum::actingAs(User::factory()->create());
        $projects = Project::factory()->count(3)->create(['client_name' => 'Acme Corp']);
        $expectedIds = array_map(function (int $position) use ($projects): int {
            return $projects[$position]->id;
        }, $expectedPositions);

        $response = $this->getJson(route('api.v1.projects.index', [
            'sortBy' => 'clientName',
            'sortOrder' => $sortOrder,
        ]));

        $response->assertOk();
        expect(array_column($response->json('data'), 'id'))->toBe($expectedIds);
    })->with([
        'ascending' => ['asc', [0, 1, 2]],
        'descending' => ['desc', [2, 1, 0]],
    ]);

    it('returns 422 when the sort is invalid', function (array $query, string $field, string $message) {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson(route('api.v1.projects.index', $query));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([$field => $message]);
    })->with([
        'database column name instead of field name' => [
            ['sortBy' => 'client_name'],
            'sortBy',
            'The sort by must be one of: id, clientName, projectName, status, priority, startDate, dueDate, createdAt, updatedAt.',
        ],
        'unknown sort order' => [
            ['sortOrder' => 'sideways'],
            'sortOrder',
            'The sort order must be either asc or desc.',
        ],
    ]);

    it('returns projects containing the search term in the client name, project name or description regardless of case', function (array $matchingAttributes, string $search) {
        Sanctum::actingAs(User::factory()->create());
        $matchingProject = Project::factory()->create($matchingAttributes);
        Project::factory()->create([
            'client_name' => 'Globex',
            'project_name' => 'Mobile App',
            'description' => 'Fitness tracker.',
        ]);

        $response = $this->getJson(route('api.v1.projects.index', ['search' => $search]));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingProject->id);
    })->with([
        'client name in lowercase' => [['client_name' => 'Acme Corporation'], 'acme'],
        'project name in uppercase' => [['project_name' => 'Property Listing Portal'], 'LISTING'],
        'description in mixed case' => [['description' => 'Track inventory across locations.'], 'InVentory'],
    ]);

    it('keeps the filters applied when searching', function () {
        Sanctum::actingAs(User::factory()->create());
        $matchingProject = Project::factory()->create([
            'client_name' => 'Acme Corporation',
            'status' => ProjectStatusEnum::Planning,
        ]);
        Project::factory()->create([
            'client_name' => 'Acme Retail',
            'status' => ProjectStatusEnum::Completed,
        ]);

        $response = $this->getJson(route('api.v1.projects.index', [
            'search' => 'acme',
            'filters' => ['status' => 'Planning'],
        ]));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingProject->id);
    });

    it('returns every project when the search is empty', function () {
        Sanctum::actingAs(User::factory()->create());
        Project::factory()->count(2)->create();

        $response = $this->getJson('/api/v1/projects?search=');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('returns 422 when the search is longer than 255 characters', function () {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson(route('api.v1.projects.index', ['search' => str_repeat('a', 256)]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['search' => 'The search field must not be greater than 255 characters.']);
    });

    it('returns 422 when a filter is invalid', function (array $filters, string $field, string $message) {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson(route('api.v1.projects.index', ['filters' => $filters]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([$field => $message]);
    })->with([
        'database column name instead of field name' => [
            ['client_name' => 'Acme Corp'],
            'filters',
            'The filters may only contain: clientName, projectName, status, priority.',
        ],
        'status value instead of label' => [
            ['status' => 'in_progress'],
            'filters.status',
            'The selected status filter is invalid. Valid statuses are: Planning, In Progress, On Hold, Completed.',
        ],
        'unknown priority' => [
            ['priority' => 'Critical'],
            'filters.priority',
            'The selected priority filter is invalid. Valid priorities are: Low, Medium, High.',
        ],
    ]);
});

describe('show', function () {
    it('returns the project', function () {
        Sanctum::actingAs(User::factory()->create());
        $project = Project::factory()->create([
            'client_name' => 'Acme Corp',
            'project_name' => 'Website Redesign',
            'description' => 'Rebuild the marketing site.',
            'status' => ProjectStatusEnum::OnHold,
            'priority' => ProjectPriorityEnum::Low,
            'start_date' => '2026-10-01',
            'due_date' => '2026-12-15',
        ]);

        $response = $this->getJson(route('api.v1.projects.show', $project));

        $response->assertOk()
            ->assertJsonPath('message', 'Project retrieved successfully')
            ->assertJson(['data' => [
                'id' => $project->id,
                'clientName' => 'Acme Corp',
                'projectName' => 'Website Redesign',
                'description' => 'Rebuild the marketing site.',
                'status' => 'On Hold',
                'priority' => 'Low',
                'startDate' => '2026-10-01',
                'dueDate' => '2026-12-15',
            ]]);
    });
});

describe('store', function () {
    it('creates the project and returns 201', function () {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson(route('api.v1.projects.store'), projectPayload());

        $response->assertCreated()
            ->assertJsonPath('message', 'Project created successfully')
            ->assertJson(['data' => [
                'clientName' => 'Acme Corp',
                'projectName' => 'Website Redesign',
                'description' => 'Rebuild the marketing site.',
                'status' => 'In Progress',
                'priority' => 'High',
                'startDate' => '2026-10-01',
                'dueDate' => '2026-12-15',
            ]]);
        $project = Project::sole();
        expect($project->id)->toBe($response->json('data.id'))
            ->and($project->client_name)->toBe('Acme Corp')
            ->and($project->project_name)->toBe('Website Redesign')
            ->and($project->description)->toBe('Rebuild the marketing site.')
            ->and($project->status)->toBe(ProjectStatusEnum::InProgress)
            ->and($project->priority)->toBe(ProjectPriorityEnum::High)
            ->and($project->start_date?->toDateString())->toBe('2026-10-01')
            ->and($project->due_date?->toDateString())->toBe('2026-12-15');
    });

    it('defaults the status to Planning and the priority to Medium when omitted', function () {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson(route('api.v1.projects.store'), [
            'clientName' => 'Acme Corp',
            'projectName' => 'Website Redesign',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'Planning')
            ->assertJsonPath('data.priority', 'Medium');
        $project = Project::sole();
        expect($project->status)->toBe(ProjectStatusEnum::Planning)
            ->and($project->priority)->toBe(ProjectPriorityEnum::Medium);
    });

    it('returns 422 when the client already has a project with the same name', function () {
        Sanctum::actingAs(User::factory()->create());
        Project::factory()->create(['client_name' => 'Acme Corp', 'project_name' => 'Website Redesign']);

        $response = $this->postJson(route('api.v1.projects.store'), projectPayload());

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['projectName' => 'A project with this name already exists for this client.']);
        $this->assertDatabaseCount('projects', 1);
    });

    it('returns 422 when the client has a soft-deleted project with the same name', function () {
        Sanctum::actingAs(User::factory()->create());
        Project::factory()->trashed()->create(['client_name' => 'Acme Corp', 'project_name' => 'Website Redesign']);

        $response = $this->postJson(route('api.v1.projects.store'), projectPayload());

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['projectName' => 'A project with this name already exists for this client.']);
        $this->assertDatabaseCount('projects', 1);
    });

    it('creates the project when only the client name or only the project name is already used', function (array $existingAttributes) {
        Sanctum::actingAs(User::factory()->create());
        Project::factory()->create($existingAttributes);

        $response = $this->postJson(route('api.v1.projects.store'), projectPayload());

        $response->assertCreated();
        $this->assertDatabaseCount('projects', 2);
    })->with([
        'same client, different project name' => [['client_name' => 'Acme Corp', 'project_name' => 'Mobile App']],
        'same project name, different client' => [['client_name' => 'Globex', 'project_name' => 'Website Redesign']],
    ]);

    it('creates the project when the due date is valid', function (?string $startDate, string $dueDate) {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson(route('api.v1.projects.store'), projectPayload([
            'startDate' => $startDate,
            'dueDate' => $dueDate,
        ]));

        $response->assertCreated();
        expect(Project::sole()->due_date?->toDateString())->toBe($dueDate);
    })->with([
        'due date on the start date' => ['2026-10-01', '2026-10-01'],
        'due date without a start date' => [null, '2026-10-01'],
    ]);

    it('returns 422 when required fields are missing', function () {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson(route('api.v1.projects.store'), []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'clientName' => 'The client name field is required.',
                'projectName' => 'The project name field is required.',
            ]);
        $this->assertDatabaseCount('projects', 0);
    });

    it('returns 422 when a field is invalid', function (array $overrides, string $field, string $message) {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson(route('api.v1.projects.store'), projectPayload($overrides));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([$field => $message]);
        $this->assertDatabaseCount('projects', 0);
    })->with([
        'status value instead of label' => [
            ['status' => 'in_progress'],
            'status',
            'The selected status is invalid. Valid statuses are: Planning, In Progress, On Hold, Completed.',
        ],
        'unknown priority' => [
            ['priority' => 'Critical'],
            'priority',
            'The selected priority is invalid. Valid priorities are: Low, Medium, High.',
        ],
        'due date before start date' => [
            ['startDate' => '2026-10-01', 'dueDate' => '2026-09-30'],
            'dueDate',
            'The due date cannot be earlier than the start date.',
        ],
        'malformed start date' => [
            ['startDate' => 'not-a-date'],
            'startDate',
            'The start date field must be a valid date.',
        ],
        'client name longer than 255 characters' => [
            ['clientName' => str_repeat('a', 256)],
            'clientName',
            'The client name field must not be greater than 255 characters.',
        ],
    ]);
});

describe('update', function () {
    it('updates the project and returns it', function () {
        Sanctum::actingAs(User::factory()->create());
        $project = Project::factory()->create();

        $response = $this->putJson(route('api.v1.projects.update', $project), projectPayload([
            'status' => 'Completed',
            'priority' => 'Low',
        ]));

        $response->assertOk()
            ->assertJsonPath('message', 'Project updated successfully')
            ->assertJson(['data' => [
                'id' => $project->id,
                'clientName' => 'Acme Corp',
                'projectName' => 'Website Redesign',
                'status' => 'Completed',
                'priority' => 'Low',
                'startDate' => '2026-10-01',
                'dueDate' => '2026-12-15',
            ]]);
        $project->refresh();
        expect($project->client_name)->toBe('Acme Corp')
            ->and($project->project_name)->toBe('Website Redesign')
            ->and($project->description)->toBe('Rebuild the marketing site.')
            ->and($project->status)->toBe(ProjectStatusEnum::Completed)
            ->and($project->priority)->toBe(ProjectPriorityEnum::Low)
            ->and($project->start_date?->toDateString())->toBe('2026-10-01')
            ->and($project->due_date?->toDateString())->toBe('2026-12-15');
    });

    it('keeps the current status and priority when they are omitted', function () {
        Sanctum::actingAs(User::factory()->create());
        $project = Project::factory()->create([
            'status' => ProjectStatusEnum::OnHold,
            'priority' => ProjectPriorityEnum::Low,
        ]);

        $response = $this->putJson(route('api.v1.projects.update', $project), [
            'clientName' => 'Acme Corp',
            'projectName' => 'Website Redesign',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'On Hold')
            ->assertJsonPath('data.priority', 'Low');
        $project->refresh();
        expect($project->status)->toBe(ProjectStatusEnum::OnHold)
            ->and($project->priority)->toBe(ProjectPriorityEnum::Low);
    });

    it('updates the project when it keeps its own client and project name', function () {
        Sanctum::actingAs(User::factory()->create());
        $project = Project::factory()->create(['client_name' => 'Acme Corp', 'project_name' => 'Website Redesign']);

        $response = $this->putJson(route('api.v1.projects.update', $project), projectPayload(['status' => 'Completed']));

        $response->assertOk();
        expect($project->refresh()->status)->toBe(ProjectStatusEnum::Completed);
    });

    it('returns 422 and leaves the project unchanged when renamed to another project of the same client', function () {
        Sanctum::actingAs(User::factory()->create());
        Project::factory()->create(['client_name' => 'Acme Corp', 'project_name' => 'Website Redesign']);
        $project = Project::factory()->create(['client_name' => 'Acme Corp', 'project_name' => 'Mobile App']);

        $response = $this->putJson(route('api.v1.projects.update', $project), projectPayload());

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['projectName' => 'A project with this name already exists for this client.']);
        expect($project->refresh()->project_name)->toBe('Mobile App');
    });

    it('returns 422 and leaves the project unchanged when the due date is before the start date', function () {
        Sanctum::actingAs(User::factory()->create());
        $project = Project::factory()->create(['project_name' => 'Original Name']);

        $response = $this->putJson(route('api.v1.projects.update', $project), projectPayload([
            'projectName' => 'Changed Name',
            'startDate' => '2026-10-01',
            'dueDate' => '2026-09-30',
        ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['dueDate' => 'The due date cannot be earlier than the start date.']);
        expect($project->refresh()->project_name)->toBe('Original Name');
    });
});

describe('destroy', function () {
    it('soft deletes the project and returns 200 with a message', function () {
        Sanctum::actingAs(User::factory()->create());
        $project = Project::factory()->create();

        $response = $this->deleteJson(route('api.v1.projects.destroy', $project));

        $response->assertOk()
            ->assertExactJson(['message' => 'Project deleted successfully', 'data' => null]);
        $this->assertSoftDeleted($project);
    });

    it('returns 404 for a soft-deleted project', function (string $method) {
        Sanctum::actingAs(User::factory()->create());
        $project = Project::factory()->trashed()->create();

        $response = $this->json($method, "/api/v1/projects/{$project->id}", projectPayload());

        $response->assertNotFound()
            ->assertExactJson(['message' => 'Project not found.']);
    })->with(['GET', 'PUT', 'DELETE']);
});
