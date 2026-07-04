<?php

namespace App\Service;

/**
 * Génère des réponses aléatoires comme si un chat tapait sur le clavier.
 */
final class CatKeyboardResponseService
{
    /** Caractères qu'un chat pourrait appuyer en marchant sur le clavier */
    private const KEYBOARD_CHARS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789      ';

    /** Petits mots que le chat pourrait produire par accident */
    private const CAT_ACCIDENTS = ['mrrp', 'prrrt', 'miaou', 'rrr', 'paw', 'zzz'];

    public function generate(): string
    {
        // Parfois le chat produit un mini-mot reconnaissable
        if (random_int(1, 6) === 1) {
            return self::CAT_ACCIDENTS[array_rand(self::CAT_ACCIDENTS)];
        }

        $length = random_int(6, 42);
        $chars = self::KEYBOARD_CHARS;
        $maxIndex = strlen($chars) - 1;
        $result = '';

        for ($i = 0; $i < $length; ++$i) {
            $result .= $chars[random_int(0, $maxIndex)];
        }

        return trim(preg_replace('/\s+/', ' ', $result) ?? $result);
    }
}
