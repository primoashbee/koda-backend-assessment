<?php

declare(strict_types=1);

use Domain\Projects\Enums\ProjectStatusEnum;

pest()->group('projects', 'domain');

test('the api status labels in workflow order', function () {
    expect(ProjectStatusEnum::values())->toBe(['Planning', 'In Progress', 'On Hold', 'Completed']);
});
