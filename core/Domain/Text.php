<?php

declare(strict_types=1);

namespace Bathys\Domain;

/**
 * The one place that cleans up typed text, so every value object that holds a
 * name does it the same way. It is a helper, not a concept in the domain, so
 * it is a function in a class rather than a value object.
 *
 * Whitespace means Unicode whitespace (a non-breaking space counts), which the
 * plain trim() function does not handle. NFC makes a composed and a decomposed
 * accent the same letter, so two spellings of one name compare as equal.
 */
final class Text
{
    public static function normalize(string $text): string
    {
        $trimmed = preg_replace('/^\s+|\s+$/u', '', $text) ?? '';
        $collapsed = preg_replace('/\s+/u', ' ', $trimmed) ?? '';

        // Whitespace (tabs and newlines included) is already collapsed above, so
        // what is left is a control character or a bidi control: refuse it.
        if (preg_match('/[\p{Cc}\x{202A}-\x{202E}\x{2066}-\x{2069}]/u', $collapsed) === 1) {
            throw InvalidText::controlCharacters();
        }

        // Compare with false, not truthiness: the string "0" is a valid value.
        $normalized = \Normalizer::normalize($collapsed, \Normalizer::FORM_C);

        return $normalized === false ? '' : $normalized;
    }
}
