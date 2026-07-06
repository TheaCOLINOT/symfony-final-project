<?php

namespace App\EventSubscriber;

use App\Event\ReservationConfirmedEvent;
use App\Event\UserRegisteredEvent;
use App\Notification\EmailVerificationNotification;
use App\Notification\ReservationConfirmedNotification;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Envoie les e-mails transactionnels via Notifier (routés en asynchrone par Messenger).
 */
final class TransactionalEmailSubscriber
{
    public function __construct(
        private readonly NotifierInterface $notifier,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[AsEventListener(event: UserRegisteredEvent::class)]
    public function onUserRegistered(UserRegisteredEvent $event): void
    {
        $user = $event->user;
        $email = $user->getEmail();
        $token = $user->getEmailVerificationToken();

        if ($email === null || $email === '' || $token === null || $token === '') {
            return;
        }

        $verificationUrl = $this->urlGenerator->generate(
            'app_verify_email',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $this->notifier->send(
            new EmailVerificationNotification($user, $verificationUrl),
            new Recipient($email),
        );
    }

    #[AsEventListener(event: ReservationConfirmedEvent::class)]
    public function onReservationConfirmed(ReservationConfirmedEvent $event): void
    {
        $user = $event->reservation->getUser();
        $email = $user?->getEmail();
        if ($email === null || $email === '') {
            return;
        }

        $this->notifier->send(
            new ReservationConfirmedNotification($event->reservation),
            new Recipient($email),
        );
    }
}
