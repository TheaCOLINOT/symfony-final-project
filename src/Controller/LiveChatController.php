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

// Live chat à distance avec un masseur chat (prestation spéciale)
#[IsGranted('ROLE_USER')]
final class LiveChatController extends AbstractController
{
    // Page principale du chat : affiche l'historique des messages
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

        // Seul le client qui a réservé peut ouvrir le chat
        $liveChatService->assertCanAccess($reservation, $user);

        // On récupère le masseur chat lié à la réservation
        $cat = $reservation->getCats()->first() ?: null;

        return $this->render('live_chat/index.html.twig', [
            'reservation' => $reservation,
            'cat' => $cat,
            'messages' => $messageRepository->findByReservationOrdered($reservation),
        ]);
    }

    // Envoi d'un message (appelé en AJAX depuis le template)
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

        // Sécurité CSRF comme pour les autres formulaires
        if (!$this->isCsrfTokenValid('live_chat_message', (string) $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'Jeton CSRF invalide.'], Response::HTTP_FORBIDDEN);
        }

        $content = (string) $request->request->get('content', '');
        $messages = $liveChatService->sendUserMessage($reservation, $user, $content);

        // Le JavaScript du template lit cette réponse JSON
        return new JsonResponse(['messages' => $messages]);
    }
}
