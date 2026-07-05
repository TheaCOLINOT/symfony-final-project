<?php

namespace App\Tests\Unit\Service;

use App\Entity\LiveChatMessage;
use App\Entity\Reservation;
use App\Service\CatKeyboardResponseService;
use App\Service\LiveChatService;
use App\Tests\Unit\TestEntityBuilderTrait;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Tests du service live chat (droits d'accès + envoi de messages).
 */
class LiveChatServiceTest extends TestCase
{
    use TestEntityBuilderTrait;

    public function testRefuseAccesSiCeNestPasLeBonClient(): void
    {
        $service = new LiveChatService(
            $this->createMock(EntityManagerInterface::class),
            new CatKeyboardResponseService(),
        );

        $owner = $this->createUser(1);
        $intrus = $this->createUser(2);
        $reservation = $this->createRemoteReservation($owner);

        $this->expectException(AccessDeniedHttpException::class);

        $service->assertCanAccess($reservation, $intrus);
    }

    public function testRefuseMessageVide(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $service = new LiveChatService($entityManager, new CatKeyboardResponseService());
        $user = $this->createUser();
        $reservation = $this->createRemoteReservation($user);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Le message ne peut pas être vide.');

        $service->sendUserMessage($reservation, $user, '   ');
    }

    public function testEnvoieMessageUtilisateurEtReponseChat(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->exactly(2))->method('persist')->with(
            $this->isInstanceOf(LiveChatMessage::class)
        );
        $entityManager->expects($this->once())->method('flush');

        $service = new LiveChatService($entityManager, new CatKeyboardResponseService());
        $user = $this->createUser();
        $reservation = $this->createRemoteReservation($user);

        $messages = $service->sendUserMessage($reservation, $user, 'Bonjour chat !');

        $this->assertCount(2, $messages);
        $this->assertSame(LiveChatMessage::SENDER_USER, $messages[0]['sender']);
        $this->assertSame('Bonjour chat !', $messages[0]['content']);
        $this->assertSame(LiveChatMessage::SENDER_CAT, $messages[1]['sender']);
        $this->assertNotSame('', $messages[1]['content']);
        $this->assertNotSame('', $messages[0]['createdAt']);
        $this->assertNotSame('', $messages[1]['createdAt']);
    }

    private function createRemoteReservation(\App\Entity\User $user): Reservation
    {
        $reservation = new Reservation();
        $reservation->setUser($user);
        $reservation->setService($this->createRemoteLiveChatService());
        $reservation->setLocation($this->createRemoteLocation());
        $reservation->setReservationDate(new \DateTime('2026-07-05 12:00:00'));
        $reservation->addCat($this->createCat());

        return $reservation;
    }
}
