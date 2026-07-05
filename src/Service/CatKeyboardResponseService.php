<?php
namespace App\Service;

/**
 * Fabrique une fausse réponse de chat masseur.
 * L'idée : des caractères au hasard, comme si le chat marchait sur le clavier.
 */
final class CatKeyboardResponseService
{
    public function generate(): string
    {
        // De temps en temps le chat "dit" un truc reconnaissable
        $hasard = random_int(1, 6);
        if ($hasard === 1) {
            $motsChat = ['mrrp', 'prrrt', 'miaou', 'rrr', 'paw', 'zzz'];

            return $motsChat[array_rand($motsChat)];
        }

        // Sinon on tape n'importe quoi au clavier
        $touches = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789      ';
        $longueur = random_int(6, 42);
        $reponse = '';

        for ($i = 0; $i < $longueur; $i++) {
            $index = random_int(0, strlen($touches) - 1);
            $reponse .= $touches[$index];
        }

        // On enlève les espaces en trop au début / à la fin
        $reponse = trim($reponse);
        $reponse = preg_replace('/\s+/', ' ', $reponse);

        return $reponse ?? '';
    }
}
