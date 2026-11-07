<?php

declare(strict_types=1);

use Bathys\Domain\Book\Author;
use Bathys\Domain\Book\InvalidAuthor;

it('keeps an author as typed after trimming and collapsing whitespace', fn () => expect(Author::fromString('  How to  Read a Book ')->toString())->toBe('How to Read a Book'));
it('treats a non-breaking space as whitespace', fn () => expect(Author::fromString("How to\u{00A0}Read")->toString())->toBe('How to Read'));
it('normalizes composed and decomposed accents to the same author', fn () => expect(Author::fromString("\u{00C9}conomie")->equals(Author::fromString("E\u{0301}conomie")))->toBeTrue());
it('rejects a blank author', fn (string $blank) => Author::fromString($blank))->with(['', '   ', "\u{00A0}\u{00A0}"])->throws(InvalidAuthor::class);
it('does not treat two spellings of a name as the same author', fn () => expect(Author::fromString('Gino Wickman')->equals(Author::fromString('gino wickman')))->toBeFalse());
