<?php

declare(strict_types=1);

namespace Domain\Projects\Actions;

use Domain\Projects\DTO\FetchProjectsDTO;
use Domain\Projects\Models\Project;
use Domain\Projects\Repositories\ProjectRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class FetchProjectsAction
{
    public function __construct(
        protected ProjectRepository $projectRepository,
    ) {}

    /**
     * Fetch projects matching the filters, in the requested order (newest first by default).
     *
     * @return LengthAwarePaginator<int, Project>
     */
    public function execute(FetchProjectsDTO $fetchProjectsDTO): LengthAwarePaginator
    {
        return $this->projectRepository->paginate(
            filters: $fetchProjectsDTO->filters,
            search: $fetchProjectsDTO->search,
            sortBy: $fetchProjectsDTO->sortBy,
            sortOrder: $fetchProjectsDTO->sortOrder,
        );
    }
}
