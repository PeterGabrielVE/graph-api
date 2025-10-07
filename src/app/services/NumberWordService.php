<?php
namespace App\Services;

use NumberFormatter;

class NumberWordService
{
    /**
     * Convierte un entero en palabras según locale ISO (ej: 'en', 'es', 'fr')
     */
    public static function numberToWords(int $n, string $locale = 'en'): string
    {
        try {
            $fmt = new NumberFormatter($locale, NumberFormatter::SPELLOUT);
            $words = $fmt->format($n);
            // Normalizar capitalización y quitar posibles comas
            if ($words === false) {
                // Fallback to en
                $fmt = new NumberFormatter('en', NumberFormatter::SPELLOUT);
                $words = $fmt->format($n);
            }
            return ucfirst((string) $words);
        } catch (\Throwable $e) {
            // fallback english
            $fmt = new NumberFormatter('en', NumberFormatter::SPELLOUT);
            return ucfirst((string) $fmt->format($n));
        }
    }
}
