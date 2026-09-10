<?php

declare(strict_types=1);

namespace Domain\Projects\Actions;

use Domain\Projects\DTO\ProjectDTO;
use Domain\Projects\Models\Project;
use Domain\Projects\Repositories\ProjectRepository;

class CreateProjectAction
{
    public function __construct(
        protected ProjectRepository $projectRepository,
    ) {}

    public function execute(ProjectDTO $projectDTO): Project
    {
        return $this->projectRepository->create($projectDTO->toAttributes());
    }
}
