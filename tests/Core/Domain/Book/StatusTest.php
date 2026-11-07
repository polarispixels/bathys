<?php

declare(strict_types=1);

use Bathys\Domain\Book\Status;

it('uses the words the spec uses as backing values', fn () => expect(Status::OnShelf->value)->toBe('shelf')->and(Status::Wanted->value)->toBe('wanted'));
