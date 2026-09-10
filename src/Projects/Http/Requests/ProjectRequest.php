<?php

declare(strict_types=1);

namespace Domain\Projects\Http\Requests;

use Domain\Projects\Enums\ProjectPriorityEnum;
use Domain\Projects\Enums\ProjectStatusEnum;
use Domain\Projects\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class ProjectRequest extends FormRequest
{
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
            'clientName' => ['required', 'string', 'max:255'],
            'projectName' => [
                'required',
                'string',
                'max:255',
                Rule::when($this->isChangingClientOrProjectName(), [
                    Rule::unique(Project::class, 'project_name')
                        ->where('client_name', $this->input('clientName'))
                        ->ignore($this->route('project')),
                ]),
            ],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::enum(ProjectStatusEnum::class)],
            'priority' => ['sometimes', Rule::enum(ProjectPriorityEnum::class)],
            /** @format date */
            'startDate' => ['nullable', 'date'],
            /** @format date */
            'dueDate' => ['nullable', 'date', 'after_or_equal:startDate'],
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
            'status.'.Enum::class => 'The selected status is invalid. Valid statuses are: '.implode(', ', ProjectStatusEnum::values()).'.',
            'priority.'.Enum::class => 'The selected priority is invalid. Valid priorities are: '.implode(', ', ProjectPriorityEnum::values()).'.',
            'dueDate.after_or_equal' => 'The due date cannot be earlier than the start date.',
            'projectName.unique' => 'A project with this name already exists for this client.',
        ];
    }

    /**
     * Creating always checks for a duplicate; an update only does when it renames the client or project,
     * so saving a project under its current names is never reported as a duplicate of itself.
     */
    private function isChangingClientOrProjectName(): bool
    {
        $project = $this->route('project');

        if (! $project instanceof Project) {
            return true;
        }

        return $project->client_name !== $this->input('clientName')
            || $project->project_name !== $this->input('projectName');
    }
}
