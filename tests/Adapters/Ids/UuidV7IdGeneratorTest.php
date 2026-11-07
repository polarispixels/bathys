<?php

declare(strict_types=1);

use App\Adapters\Ids\UuidV7IdGenerator;

it('makes distinct, well-formed version 7 ids', function (): void {
    $generator = new UuidV7IdGenerator;

    $first = $generator->nextBookId()->toString();
    $second = $generator->nextBookId()->toString();

    expect($first)->not->toBe($second);
    expect($first)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/');
    expect($second[14])->toBe('7');
});
