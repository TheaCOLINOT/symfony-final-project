<?php
namespace App\Service;
use App\Entity\LiveChatMessage;
use App\Entity\Reservation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Service pour le live chat à distance.
 * Quand l'utilisateur envoie un message, on enregistre sa réponse
 * puis on génère automatiquement celle du chat masseur.
 */
final class LiveChatService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CatKeyboardResponseService $catKeyboardResponseService,
    ) {
    }

    // Vérifie que l'utilisateur connecté a le droit d'ouvrir ce chat
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
     * Enregistre le message du client + la réponse auto du chat.
     * Retourne un tableau simple pour le JSON du contrôleur.
     */
    public function sendUserMessage(Reservation $reservation, User $user, string $content): array
    {
        $this->assertCanAccess($reservation, $user);

        $content = trim($content);
        if ($content === '') {
            throw new BadRequestHttpException('Le message ne peut pas être vide.');
        }

        // Message écrit par l'utilisateur
        $userMessage = new LiveChatMessage();
        $userMessage->setReservation($reservation);
        $userMessage->setSender(LiveChatMessage::SENDER_USER);
        $userMessage->setContent($content);
        $userMessage->setCreatedAt(new \DateTime());

        // Réponse du chat (chaîne aléatoire générée par le service dédié)
        $catReply = $this->catKeyboardResponseService->generate();
        $catMessage = new LiveChatMessage();
        $catMessage->setReservation($reservation);
        $catMessage->setSender(LiveChatMessage::SENDER_CAT);
        $catMessage->setContent($catReply);
        $catMessage->setCreatedAt(new \DateTime());

        $this->entityManager->persist($userMessage);
        $this->entityManager->persist($catMessage);
        $this->entityManager->flush();

        // Format attendu par le JavaScript côté navigateur
        return [
            [
                'sender' => $userMessage->getSender(),
                'content' => $userMessage->getContent(),
                'createdAt' => $userMessage->getCreatedAt()?->format('H:i') ?? '',
            ],
            [
                'sender' => $catMessage->getSender(),
                'content' => $catMessage->getContent(),
                'createdAt' => $catMessage->getCreatedAt()?->format('H:i') ?? '',
            ],
        ];
    }
}
