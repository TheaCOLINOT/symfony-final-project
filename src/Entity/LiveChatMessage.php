<?php

namespace App\Entity;

use App\Repository\LiveChatMessageRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Message échangé pendant une session de live chat à distance.
 */
#[ORM\Entity(repositoryClass: LiveChatMessageRepository::class)]
#[ORM\Table(name: 'live_chat_message')]
class LiveChatMessage
{
    public const SENDER_USER = 'user';
    public const SENDER_CAT = 'cat';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Reservation::class)]
    #[ORM\JoinColumn(name: 'reservation_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Reservation $reservation = null;

    #[ORM\Column(length: 10)]
    private string $sender = self::SENDER_USER;

    #[ORM\Column(type: 'text')]
    private string $content = '';

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReservation(): ?Reservation
    {
        return $this->reservation;
    }

    public function setReservation(Reservation $reservation): self
    {
        $this->reservation = $reservation;

        return $this;
    }

    public function getSender(): string
    {
        return $this->sender;
    }

    public function setSender(string $sender): self
    {
        $this->sender = $sender;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt instanceof \DateTime
            ? $createdAt
            : \DateTime::createFromInterface($createdAt);

        return $this;
    }
}
