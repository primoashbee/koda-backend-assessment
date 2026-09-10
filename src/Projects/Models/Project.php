<?php

declare(strict_types=1);

namespace Domain\Projects\Models;

use Carbon\CarbonInterface;
use Domain\Projects\Database\Factories\ProjectFactory;
use Domain\Projects\Enums\ProjectPriorityEnum;
use Domain\Projects\Enums\ProjectStatusEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $client_name
 * @property string $project_name
 * @property string|null $description
 * @property ProjectStatusEnum $status
 * @property ProjectPriorityEnum $priority
 * @property CarbonInterface|null $start_date
 * @property CarbonInterface|null $due_date
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 */
#[Fillable(['client_name', 'project_name', 'description', 'status', 'priority', 'start_date', 'due_date'])]
#[UseFactory(ProjectFactory::class)]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The model's default values for attributes, mirroring the database defaults.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'status' => 'Planning',
        'priority' => 'Medium',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProjectStatusEnum::class,
            'priority' => ProjectPriorityEnum::class,
            'start_date' => 'date',
            'due_date' => 'date',
        ];
    }

    /**
     * Narrow the query by each filter that is present; missing keys are ignored.
     *
     * @param  Builder<Project>  $query
     * @param  array<string, mixed>  $filters
     */
    public function scopeApplyFilter(Builder $query, array $filters = []): void
    {
        if (array_key_exists('client_name', $filters)) {
            $query->where('client_name', $filters['client_name']);
        }

        if (array_key_exists('project_name', $filters)) {
            $query->where('project_name', $filters['project_name']);
        }

        if (array_key_exists('status', $filters)) {
            $query->where('status', $filters['status']);
        }

        if (array_key_exists('priority', $filters)) {
            $query->where('priority', $filters['priority']);
        }
    }

    /**
     * Match projects whose client name, project name or description contains the term, ignoring case.
     *
     * @param  Builder<Project>  $query
     */
    public function scopeApplySearch(Builder $query, ?string $search = null): void
    {
        if ($search === null) {
            return;
        }

        // Grouped: ungrouped ORs would escape the other constraints on the query and widen the result.
        $query->where(function (Builder $query) use ($search): void {
            $query->whereLike('client_name', "%{$search}%")
                ->orWhereLike('project_name', "%{$search}%")
                ->orWhereLike('description', "%{$search}%");
        });
    }
}
