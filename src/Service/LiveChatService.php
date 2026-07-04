<?php

namespace App\Service;

use App\Entity\LiveChatMessage;
use App\Entity\Reservation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Gère l'envoi de messages dans le live chat à distance.
 */
final class LiveChatService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CatKeyboardResponseService $catKeyboardResponseService,
    ) {
    }

    public function assertCanAccess(Reservation $reservation, User $user): void
    {
        if ($reservation->getUser()?->getId() !== $user->getId()) {
            throw new AccessDeniedHttpException('Vous ne pouvez pas accéder à ce live chat.');
        }

        if (!$reservation->getService()?->isRemoteLiveChat()) {
            throw new AccessDeniedHttpException('Cette réservation ne propose pas de live chat.');
        }
    }

    /**
     * @return list<array{sender: string, content: string, createdAt: string}>
     */
    public function sendUserMessage(Reservation $reservation, User $user, string $content): array
    {
        $this->assertCanAccess($reservation, $user);

        $trimmed = trim($content);
        if ($trimmed === '') {
            throw new BadRequestHttpException('Le message ne peut pas être vide.');
        }

        $userMessage = $this->createMessage($reservation, LiveChatMessage::SENDER_USER, $trimmed);
        $catMessage = $this->createMessage(
            $reservation,
            LiveChatMessage::SENDER_CAT,
            $this->catKeyboardResponseService->generate(),
        );

        $this->entityManager->persist($userMessage);
        $this->entityManager->persist($catMessage);
        $this->entityManager->flush();

        return [
            $this->serializeMessage($userMessage),
            $this->serializeMessage($catMessage),
        ];
    }

    private function createMessage(Reservation $reservation, string $sender, string $content): LiveChatMessage
    {
        $message = new LiveChatMessage();
        $message->setReservation($reservation);
        $message->setSender($sender);
        $message->setContent($content);
        $message->setCreatedAt(new \DateTime());

        return $message;
    }

    /**
     * @return array{sender: string, content: string, createdAt: string}
     */
    public function serializeMessage(LiveChatMessage $message): array
    {
        return [
            'sender' => $message->getSender(),
            'content' => $message->getContent(),
            'createdAt' => $message->getCreatedAt()?->format('H:i') ?? '',
        ];
    }
}
