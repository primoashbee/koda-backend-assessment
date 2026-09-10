<?php

declare(strict_types=1);

use Domain\Users\Models\User;
use Illuminate\Support\Facades\Gate;

pest()->group('docs');

it('documents project endpoints as bearer protected and auth endpoints as public', function () {
    Gate::define('viewApiDocs', function (?User $user): bool {
        return true;
    });

    $response = $this->getJson(route('scramble.docs.document'));

    $response->assertOk()
        ->assertJsonPath('components.securitySchemes.http.scheme', 'bearer')
        ->assertJsonPath('security', [['http' => []]])
        ->assertJsonPath('paths./auth/register.post.security', [])
        ->assertJsonPath('paths./auth/login.post.security', [])
        ->assertJsonMissingPath('paths./projects.get.security')
        ->assertJsonMissingPath('paths./projects/{project}.delete.security')
        ->assertJsonPath('components.schemas.ProjectStatusEnum.enum', ['Planning', 'In Progress', 'On Hold', 'Completed'])
        ->assertJsonPath('components.schemas.ProjectPriorityEnum.enum', ['Low', 'Medium', 'High']);
    expect(array_keys($response->json('paths')))->toContain('/auth/register', '/auth/login', '/projects', '/projects/{project}');
});

it('documents the message and data envelope returned by the response macros', function () {
    Gate::define('viewApiDocs', function (?User $user): bool {
        return true;
    });

    $response = $this->getJson(route('scramble.docs.document'));

    $response->assertOk()
        ->assertJsonPath('paths./projects.post.responses.201.content.application/json.schema.properties.message.type', 'string')
        ->assertJsonPath('paths./projects.post.responses.201.content.application/json.schema.properties.data.$ref', '#/components/schemas/ProjectResource')
        ->assertJsonPath('paths./projects.get.responses.200.content.application/json.schema.properties.meta.required', ['currentPage', 'lastPage', 'perPage', 'total'])
        ->assertJsonPath('paths./auth/register.post.responses.201.content.application/json.schema.properties.data.properties.token.type', 'string');
});

it('renders the swagger ui', function () {
    Gate::define('viewApiDocs', function (?User $user): bool {
        return true;
    });

    $response = $this->get(route('scramble.docs.ui'));

    $response->assertOk()
        ->assertSee('SwaggerUIBundle', false);
});

it('returns 403 for the docs when the viewer is not allowed outside the local environment', function () {
    $response = $this->getJson(route('scramble.docs.document'));

    $response->assertForbidden();
});
