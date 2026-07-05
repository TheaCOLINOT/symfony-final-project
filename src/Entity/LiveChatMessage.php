<?php
namespace App\Entity;
use App\Repository\LiveChatMessageRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité LiveChatMessage : un message dans le live chat à distance.
 * Chaque ligne est soit de l'utilisateur, soit du chat masseur (réponse auto).
 */
#[ORM\Entity(repositoryClass: LiveChatMessageRepository::class)]
#[ORM\Table(name: 'live_chat_message')]
class LiveChatMessage
{
    public const SENDER_USER = 'user'; // message du client
    public const SENDER_CAT = 'cat';   // réponse du masseur chat

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Reservation::class)]
    #[ORM\JoinColumn(name: 'reservation_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Reservation $reservation = null; // réservation liée au chat

    #[ORM\Column(length: 10)]
    private string $sender = self::SENDER_USER; // user ou cat

    #[ORM\Column(type: 'text')]
    private string $content = ''; // texte du message

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null; // date d'envoi

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

    /**
     * Doctrine veut un DateTime classique, pas un DateTimeImmutable.
     * Même problème que sur Reservation, du coup on convertit ici.
     */
    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        if ($createdAt instanceof \DateTime) {
            $this->createdAt = $createdAt;
        } else {
            $this->createdAt = \DateTime::createFromInterface($createdAt);
        }

        return $this;
    }
}
