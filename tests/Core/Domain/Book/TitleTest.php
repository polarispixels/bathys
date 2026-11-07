<?php

declare(strict_types=1);

use Bathys\Domain\Book\InvalidTitle;
use Bathys\Domain\Book\Title;

it('keeps a title as typed after trimming and collapsing whitespace', fn () => expect(Title::fromString('  How to  Read a Book ')->toString())->toBe('How to Read a Book'));
it('treats a non-breaking space as whitespace', fn () => expect(Title::fromString("How to\u{00A0}Read")->toString())->toBe('How to Read'));
it('normalizes composed and decomposed accents to the same title', fn () => expect(Title::fromString("\u{00C9}conomie")->equals(Title::fromString("E\u{0301}conomie")))->toBeTrue());
it('rejects a blank title', fn (string $blank) => Title::fromString($blank))->with(['', '   ', "\u{00A0}\u{00A0}"])->throws(InvalidTitle::class);
it('compares titles exactly after normalization', fn () => expect(Title::fromString('Good to Great')->equals(Title::fromString('good to great')))->toBeFalse());
it('accepts a title of "0" as a real title', fn () => expect(Title::fromString('0')->toString())->toBe('0'));
