<?php

namespace App\Notification;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Notification\EmailNotificationInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;

/**
 * E-mail transactionnel de bienvenue après inscription.
 */
final class UserRegisteredNotification extends Notification implements EmailNotificationInterface
{
    public function __construct(
        private readonly User $user,
    ) {
        parent::__construct('Bienvenue sur Salon de Massage', ['email']);
    }

    public function asEmailMessage(EmailRecipientInterface $recipient, ?string $transport = null): EmailMessage
    {
        $email = NotificationEmail::asPublicEmail()
            ->to($recipient->getEmail())
            ->subject('Confirmation de votre inscription')
            ->htmlTemplate('email/user_registered.html.twig')
            ->context(['user' => $this->user]);

        return new EmailMessage($email);
    }
}
