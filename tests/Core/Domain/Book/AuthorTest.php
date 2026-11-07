<?php

declare(strict_types=1);

use Bathys\Domain\Book\Author;
use Bathys\Domain\Book\InvalidAuthor;

it('keeps an author as typed after trimming and collapsing whitespace', fn () => expect(Author::fromString('  Mortimer J.  Adler ')->toString())->toBe('Mortimer J. Adler'));
it('treats a non-breaking space as whitespace', fn () => expect(Author::fromString("Charles\u{00A0}Van Doren")->toString())->toBe('Charles Van Doren'));
it('normalizes composed and decomposed accents to the same author', fn () => expect(Author::fromString("\u{00C9}conomie")->equals(Author::fromString("E\u{0301}conomie")))->toBeTrue());
it('rejects a blank author', fn (string $blank) => Author::fromString($blank))->with(['', '   ', "\u{00A0}\u{00A0}"])->throws(InvalidAuthor::class);
it('does not treat two spellings of a name as the same author', fn () => expect(Author::fromString('Gino Wickman')->equals(Author::fromString('gino wickman')))->toBeFalse());
it('accepts an author of "0" as a real name', fn () => expect(Author::fromString('0')->toString())->toBe('0'));
