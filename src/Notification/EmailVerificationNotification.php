<?php

namespace App\Notification;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Notification\EmailNotificationInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;

/**
 * E-mail de validation d'inscription avec lien de confirmation.
 */
final class EmailVerificationNotification extends Notification implements EmailNotificationInterface
{
    public function __construct(
        private readonly User $user,
        private readonly string $verificationUrl,
    ) {
        parent::__construct('Confirmez votre inscription', ['email']);
    }

    public function asEmailMessage(EmailRecipientInterface $recipient, ?string $transport = null): EmailMessage
    {
        $email = NotificationEmail::asPublicEmail()
            ->to($recipient->getEmail())
            ->subject('Confirmez votre inscription — Salon de Massage')
            ->htmlTemplate('email/email_verification.html.twig')
            ->context([
                'user' => $this->user,
                'verificationUrl' => $this->verificationUrl,
            ]);

        return new EmailMessage($email);
    }
}
