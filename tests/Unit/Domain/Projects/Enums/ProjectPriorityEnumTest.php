<?php

declare(strict_types=1);

use Domain\Projects\Enums\ProjectPriorityEnum;

pest()->group('projects', 'domain');

test('the api priority labels from lowest to highest', function () {
    expect(ProjectPriorityEnum::values())->toBe(['Low', 'Medium', 'High']);
});
