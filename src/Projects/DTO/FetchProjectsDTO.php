<?php

declare(strict_types=1);

namespace Domain\Projects\DTO;

class FetchProjectsDTO
{
    /**
     * @param  array<string, string>  $filters
     * @param  'asc'|'desc'  $sortOrder
     */
    public function __construct(
        public ?string $search = null,
        public array $filters = [],
        public string $sortBy = 'id',
        public string $sortOrder = 'desc',
    ) {}
}
