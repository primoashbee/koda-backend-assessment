<?php

declare(strict_types=1);

namespace Domain\Projects\Repositories;

use Domain\Projects\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectRepository
{
    /**
     * Paginate projects matching the filters and search, in the given order.
     *
     * @param  array<string, string>  $filters
     * @param  'asc'|'desc'  $sortOrder
     * @return LengthAwarePaginator<int, Project>
     */
    public function paginate(array $filters, ?string $search, string $sortBy, string $sortOrder): LengthAwarePaginator
    {
        return Project::query()
            ->applyFilter($filters)
            ->applySearch($search)
            ->orderBy($sortBy, $sortOrder)
            ->when($sortBy !== 'id', function (Builder $query) use ($sortOrder): void {
                // Tie-breaker so rows sharing a value keep a stable order across pages.
                $query->orderBy('id', $sortOrder);
            })
            ->paginate()
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Project
    {
        return Project::create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Project $project, array $attributes): Project
    {
        $project->update($attributes);

        return $project;
    }

    public function delete(Project $project): void
    {
        $project->delete();
    }
}
