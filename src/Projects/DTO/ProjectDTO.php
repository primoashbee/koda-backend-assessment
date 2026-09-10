<?php

declare(strict_types=1);

namespace Domain\Projects\DTO;

use Domain\Projects\Enums\ProjectPriorityEnum;
use Domain\Projects\Enums\ProjectStatusEnum;

class ProjectDTO
{
    public function __construct(
        public string $clientName,
        public string $projectName,
        public ?string $description,
        public ?ProjectStatusEnum $status,
        public ?ProjectPriorityEnum $priority,
        public ?string $startDate,
        public ?string $dueDate,
    ) {}

    /**
     * Get the model attributes, leaving out a status or priority that was not provided
     * so the project keeps its current (or default) value.
     *
     * @return array<string, string|ProjectStatusEnum|ProjectPriorityEnum|null>
     */
    public function toAttributes(): array
    {
        $attributes = [
            'client_name' => $this->clientName,
            'project_name' => $this->projectName,
            'description' => $this->description,
            'start_date' => $this->startDate,
            'due_date' => $this->dueDate,
        ];

        if ($this->status !== null) {
            $attributes['status'] = $this->status;
        }

        if ($this->priority !== null) {
            $attributes['priority'] = $this->priority;
        }

        return $attributes;
    }
}
