<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\User;
use App\Repository\LiveChatMessageRepository;
use App\Service\LiveChatService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class LiveChatController extends AbstractController
{
    #[Route('/live-chat/{reservationId}', name: 'app_live_chat', methods: ['GET'])]
    public function index(
        #[MapEntity(id: 'reservationId')] Reservation $reservation,
        LiveChatService $liveChatService,
        LiveChatMessageRepository $messageRepository,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $liveChatService->assertCanAccess($reservation, $user);

        $cat = $reservation->getCats()->first() ?: null;

        return $this->render('live_chat/index.html.twig', [
            'reservation' => $reservation,
            'cat' => $cat,
            'messages' => $messageRepository->findByReservationOrdered($reservation),
        ]);
    }

    #[Route('/live-chat/{reservationId}/message', name: 'app_live_chat_message', methods: ['POST'])]
    public function sendMessage(
        #[MapEntity(id: 'reservationId')] Reservation $reservation,
        Request $request,
        LiveChatService $liveChatService,
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('live_chat_message', (string) $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'Jeton CSRF invalide.'], Response::HTTP_FORBIDDEN);
        }

        $content = (string) $request->request->get('content', '');
        $messages = $liveChatService->sendUserMessage($reservation, $user, $content);

        return new JsonResponse(['messages' => $messages]);
    }
}
