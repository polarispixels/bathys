<?php

declare(strict_types=1);

use App\Adapters\Clock\SystemClock;

it('reports the current instant in UTC', function (): void {
    $now = (new SystemClock)->now();

    expect($now->getTimezone()->getName())->toBe('UTC');
    expect(abs($now->getTimestamp() - time()))->toBeLessThanOrEqual(2);
});
