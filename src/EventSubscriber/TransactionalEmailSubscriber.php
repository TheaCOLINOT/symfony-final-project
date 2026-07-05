<?php

namespace App\EventSubscriber;

use App\Event\ReservationConfirmedEvent;
use App\Event\UserRegisteredEvent;
use App\Notification\ReservationConfirmedNotification;
use App\Notification\UserRegisteredNotification;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\Recipient;

/**
 * Envoie les e-mails transactionnels via Notifier (routés en asynchrone par Messenger).
 */
final class TransactionalEmailSubscriber
{
    public function __construct(
        private readonly NotifierInterface $notifier,
    ) {
    }

    #[AsEventListener(event: UserRegisteredEvent::class)]
    public function onUserRegistered(UserRegisteredEvent $event): void
    {
        $email = $event->user->getEmail();
        if ($email === null || $email === '') {
            return;
        }

        $this->notifier->send(
            new UserRegisteredNotification($event->user),
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
