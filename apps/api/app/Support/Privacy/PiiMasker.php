<?php

declare(strict_types=1);

namespace App\Support\Privacy;

/**
 * Replaces personal data in free text with placeholders before it leaves
 * the API for a model (CPF/CNPJ, card numbers, e-mails, phone numbers).
 */
final class PiiMasker
{
    /** @var array<string, string> pattern => placeholder, applied in order (phones before cards: same digit count) */
    private const array PATTERNS = [
        '/[\w.+-]+@[\w-]+(?:\.[\w-]+)+/u' => '[EMAIL]',
        '/\b\d{2}\.?\d{3}\.?\d{3}\/?\d{4}-?\d{2}\b/u' => '[CNPJ]',
        '/\b\d{3}\.?\d{3}\.?\d{3}-?\d{2}\b/u' => '[CPF]',
        '/(?:\+?55\s?)?\(?\b\d{2}\)?\s?9?\d{4}[-\s]?\d{4}\b/u' => '[TELEFONE]',
        '/\b(?:\d[ .-]?){13,19}\b/u' => '[CARTAO]',
    ];

    public static function mask(string $text): string
    {
        return (string) preg_replace(array_keys(self::PATTERNS), array_values(self::PATTERNS), $text);
    }
}
