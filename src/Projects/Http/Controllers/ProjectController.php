<?php

declare(strict_types=1);

namespace Domain\Projects\Http\Controllers;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Domain\Projects\Actions\CreateProjectAction;
use Domain\Projects\Actions\DeleteProjectAction;
use Domain\Projects\Actions\FetchProjectsAction;
use Domain\Projects\Actions\UpdateProjectAction;
use Domain\Projects\DTO\FetchProjectsDTO;
use Domain\Projects\DTO\ProjectDTO;
use Domain\Projects\Enums\ProjectPriorityEnum;
use Domain\Projects\Enums\ProjectStatusEnum;
use Domain\Projects\Http\Requests\FetchProjectsRequest;
use Domain\Projects\Http\Requests\ProjectRequest;
use Domain\Projects\Http\Resources\ProjectResource;
use Domain\Projects\Models\Project;
use Illuminate\Http\JsonResponse;

#[Group('Projects', weight: 2)]
class ProjectController extends Controller
{
    /**
     * List projects, newest first unless a sort is given.
     *
     * @response array{message: string, data: list<ProjectResource>, meta: array{currentPage: int, lastPage: int, perPage: int, total: int}, links: array{first: string, last: string, prev: string|null, next: string|null}}
     */
    public function index(
        FetchProjectsRequest $request,
        FetchProjectsAction $fetchProjectsAction,
    ): JsonResponse {
        $fetchProjectsDTO = new FetchProjectsDTO(
            search: $request->validated('search'),
            filters: $request->filtersByColumn(),
            sortBy: $request->sortColumn(),
            sortOrder: $request->validated('sortOrder', 'desc'),
        );

        $projects = $fetchProjectsAction->execute($fetchProjectsDTO)
            ->through(function (Project $project): array {
                return ProjectResource::make($project)->resolve();
            });

        return response()->paginated($projects, 'Projects fetched successfully');
    }

    /**
     * Create a project.
     *
     * @response JsonResponse<array{message: string, data: ProjectResource}, 201>
     */
    public function store(ProjectRequest $request, CreateProjectAction $createProjectAction): JsonResponse
    {
        $project = $createProjectAction->execute($this->makeProjectDTO($request));

        return response()->created(ProjectResource::make($project), 'Project created successfully');
    }

    /**
     * Show a single project.
     *
     * @response array{message: string, data: ProjectResource}
     */
    public function show(Project $project): JsonResponse
    {
        return response()->success(ProjectResource::make($project), 'Project retrieved successfully');
    }

    /**
     * Update a project.
     *
     * @response array{message: string, data: ProjectResource}
     */
    public function update(
        ProjectRequest $request,
        Project $project,
        UpdateProjectAction $updateProjectAction,
    ): JsonResponse {
        $updatedProject = $updateProjectAction->execute($project, $this->makeProjectDTO($request));

        return response()->updated(ProjectResource::make($updatedProject), 'Project updated successfully');
    }

    /**
     * Delete a project.
     *
     * @response array{message: string, data: null}
     */
    public function destroy(Project $project, DeleteProjectAction $deleteProjectAction): JsonResponse
    {
        $deleteProjectAction->execute($project);

        return response()->deleted(message: 'Project deleted successfully');
    }

    private function makeProjectDTO(ProjectRequest $request): ProjectDTO
    {
        return new ProjectDTO(
            clientName: $request->validated('clientName'),
            projectName: $request->validated('projectName'),
            description: $request->validated('description'),
            status: $request->enum('status', ProjectStatusEnum::class),
            priority: $request->enum('priority', ProjectPriorityEnum::class),
            startDate: $request->validated('startDate'),
            dueDate: $request->validated('dueDate'),
        );
    }
}
