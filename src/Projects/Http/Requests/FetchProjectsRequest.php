<?php

declare(strict_types=1);

namespace Domain\Projects\Http\Requests;

use Domain\Projects\Enums\ProjectPriorityEnum;
use Domain\Projects\Enums\ProjectStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class FetchProjectsRequest extends FormRequest
{
    /**
     * Fields a client may sort by. Column names cannot be bound as query parameters,
     * so `sortBy` must be restricted to this list before it reaches `orderBy()`.
     *
     * @var list<string>
     */
    public const SORTABLE_FIELDS = [
        'id',
        'clientName',
        'projectName',
        'status',
        'priority',
        'startDate',
        'dueDate',
        'createdAt',
        'updatedAt',
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            /** Case-insensitive partial match on clientName, projectName or description. */
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filters' => ['sometimes', 'array:clientName,projectName,status,priority'],
            'filters.clientName' => ['sometimes', 'string', 'max:255'],
            'filters.projectName' => ['sometimes', 'string', 'max:255'],
            'filters.status' => ['sometimes', Rule::enum(ProjectStatusEnum::class)],
            'filters.priority' => ['sometimes', Rule::enum(ProjectPriorityEnum::class)],
            'sortBy' => ['sometimes', Rule::in(self::SORTABLE_FIELDS)],
            'sortOrder' => ['sometimes', Rule::in(['asc', 'desc'])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'filters.array' => 'The filters may only contain: clientName, projectName, status, priority.',
            'filters.status.'.Enum::class => 'The selected status filter is invalid. Valid statuses are: '.implode(', ', ProjectStatusEnum::values()).'.',
            'filters.priority.'.Enum::class => 'The selected priority filter is invalid. Valid priorities are: '.implode(', ', ProjectPriorityEnum::values()).'.',
            'sortBy.in' => 'The sort by must be one of: '.implode(', ', self::SORTABLE_FIELDS).'.',
            'sortOrder.in' => 'The sort order must be either asc or desc.',
        ];
    }

    /**
     * Get the validated filters keyed by database column (`clientName` becomes `client_name`).
     *
     * @return array<string, string>
     */
    public function filtersByColumn(): array
    {
        $filters = [];

        foreach ($this->validated('filters', []) as $field => $value) {
            $filters[Str::snake($field)] = $value;
        }

        return $filters;
    }

    /**
     * Get the database column for the validated sort field, defaulting to `id`.
     */
    public function sortColumn(): string
    {
        return Str::snake($this->validated('sortBy', 'id'));
    }
}
