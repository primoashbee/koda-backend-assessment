<?php

declare(strict_types=1);

namespace Domain\Projects\Enums;

enum ProjectPriorityEnum: string
{
    case Low = 'Low';
    case Medium = 'Medium';
    case High = 'High';

    /**
     * Get the backing value of every case.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
