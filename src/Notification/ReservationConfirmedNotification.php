<?php

namespace App\Notification;

use App\Entity\Reservation;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Notification\EmailNotificationInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;

/**
 * E-mail transactionnel de confirmation de réservation (commande).
 */
final class ReservationConfirmedNotification extends Notification implements EmailNotificationInterface
{
    public function __construct(
        private readonly Reservation $reservation,
    ) {
        parent::__construct('Confirmation de réservation', ['email']);
    }

    public function asEmailMessage(EmailRecipientInterface $recipient, ?string $transport = null): EmailMessage
    {
        $email = NotificationEmail::asPublicEmail()
            ->to($recipient->getEmail())
            ->subject('Votre réservation est confirmée')
            ->htmlTemplate('email/reservation_confirmed.html.twig')
            ->context(['reservation' => $this->reservation]);

        return new EmailMessage($email);
    }
}
