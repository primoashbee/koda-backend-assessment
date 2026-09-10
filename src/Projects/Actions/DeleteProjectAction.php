<?php

declare(strict_types=1);

namespace Domain\Projects\Actions;

use Domain\Projects\Models\Project;
use Domain\Projects\Repositories\ProjectRepository;

class DeleteProjectAction
{
    public function __construct(
        protected ProjectRepository $projectRepository,
    ) {}

    public function execute(Project $project): void
    {
        $this->projectRepository->delete($project);
    }
}
