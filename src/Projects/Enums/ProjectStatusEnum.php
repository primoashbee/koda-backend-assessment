<?php

declare(strict_types=1);

namespace Domain\Projects\Enums;

enum ProjectStatusEnum: string
{
    case Planning = 'Planning';
    case InProgress = 'In Progress';
    case OnHold = 'On Hold';
    case Completed = 'Completed';

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
