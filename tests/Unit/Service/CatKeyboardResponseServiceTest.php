<?php

namespace App\Tests\Unit\Service;

use App\Service\CatKeyboardResponseService;
use PHPUnit\Framework\TestCase;

/**
 * Tests du générateur de réponses "chat sur le clavier".
 */
class CatKeyboardResponseServiceTest extends TestCase
{
    private CatKeyboardResponseService $service;

    protected function setUp(): void
    {
        $this->service = new CatKeyboardResponseService();
    }

    public function testGenerateRetourneUneChaineNonVide(): void
    {
        $reponse = $this->service->generate();

        $this->assertNotSame('', $reponse);
        $this->assertGreaterThan(0, strlen($reponse));
    }

    public function testGeneratePeutRetournerUnMotDeChat(): void
    {
        $motsPossibles = ['mrrp', 'prrrt', 'miaou', 'rrr', 'paw', 'zzz'];
        $trouve = false;

        // On relance plusieurs fois car le mot est aléatoire (1 chance sur 6)
        for ($i = 0; $i < 80; $i++) {
            if (in_array($this->service->generate(), $motsPossibles, true)) {
                $trouve = true;
                break;
            }
        }

        $this->assertTrue($trouve, 'On devrait tomber sur un mot de chat au bout de plusieurs essais.');
    }

    public function testGenerateRetourneUneChaineRaisonnableQuandCeNestPasUnMot(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $reponse = $this->service->generate();

            // Longueur max prévue dans le service + marge
            $this->assertLessThanOrEqual(42, strlen($reponse));
        }
    }
}
