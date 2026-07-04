<?php
namespace App\Entity;
use App\Enum\ReservationStatus;
use App\Repository\ReservationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
/**
 * Entité Reservation : représente une réservation de massage par un client.
 * Elle lie un utilisateur, une prestation, un salon, un créneau horaire
 * et les chats masseurs assignés à la séance.
 */
#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\Table(name: 'reservation')]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null; // Identifiant unique en base
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private ?User $user = null; // Client qui a fait la réservation
    #[ORM\ManyToOne(targetEntity: Service::class)]
    #[ORM\JoinColumn(name: 'service_id', referencedColumnName: 'id', nullable: false)]
    private ?Service $service = null; // Prestation réservée (relation vers l'entité)
    #[ORM\ManyToOne(targetEntity: Location::class)]
    #[ORM\JoinColumn(name: 'location_id', referencedColumnName: 'id', nullable: false)]
    private ?Location $location = null; // Salon où aura lieu le massage
    #[ORM\Column(name: 'service', type: 'text', nullable: true)]
    private ?string $serviceLabel = null; // Libellé de la prestation (copie pour l'historique)
    #[ORM\Column(name: 'cat', type: 'text', nullable: true)]
    private ?string $catLabel = null; // Libellé du chat masseur (copie pour l'historique)
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $date = null; // Date du rendez-vous
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $hour = null; // Heure du rendez-vous (format H:i)
    #[ORM\Column(name: 'reservation_date', type: 'datetime')]
    private ?\DateTimeInterface $reservationDate = null; // Date/heure complète de la réservation
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $duration = null; // Durée du massage réservé
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $price = null; // Prix au moment de la réservation
    #[ORM\Column(length: 20)]
    private string $status = ReservationStatus::Confirmed->value; // Statut de la réservation (confirmée, etc.)
    /**
     * @var Collection<int, Cat>
     */
    #[ORM\ManyToMany(targetEntity: Cat::class)]
    #[ORM\JoinTable(name: 'reservation_cat')]
    #[ORM\JoinColumn(name: 'reservation_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'cat_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Collection $cats; // Chats masseurs assignés à cette réservation
    /**
     * Initialise la collection vide des chats masseurs.
     */
    public function __construct()
    {
        $this->cats = new ArrayCollection();
    }
    /** Retourne l'identifiant de la réservation. */
    public function getId(): ?int
    {
        return $this->id;
    }
    /** Retourne le client qui a réservé. */
    public function getUser(): ?User
    {
        return $this->user;
    }
    /** Associe le client à la réservation. */
    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }
    /** Retourne la prestation réservée (entité Service). */
    public function getService(): ?Service
    {
        return $this->service;
    }
    /** Associe la prestation à la réservation. */
    public function setService(Service $service): self
    {
        $this->service = $service;
        return $this;
    }
    /** Retourne le salon où aura lieu le massage. */
    public function getLocation(): ?Location
    {
        return $this->location;
    }
    /** Associe le salon à la réservation. */
    public function setLocation(Location $location): self
    {
        $this->location = $location;
        return $this;
    }
    /** Retourne le libellé texte de la prestation (snapshot). */
    public function getServiceLabel(): ?string
    {
        return $this->serviceLabel;
    }
    /** Définit le libellé texte de la prestation. */
    public function setServiceLabel(?string $serviceLabel): self
    {
        $this->serviceLabel = $serviceLabel;
        return $this;
    }
    /** Retourne le libellé texte du chat masseur (snapshot). */
    public function getCatLabel(): ?string
    {
        return $this->catLabel;
    }
    /** Définit le libellé texte du chat masseur. */
    public function setCatLabel(?string $catLabel): self
    {
        $this->catLabel = $catLabel;
        return $this;
    }
    /** Retourne la date du rendez-vous. */
    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }
    /** Définit la date du rendez-vous. */
    public function setDate(?\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }
    /** Retourne l'heure du rendez-vous. */
    public function getHour(): ?string
    {
        return $this->hour;
    }
    /** Définit l'heure du rendez-vous. */
    public function setHour(?string $hour): self
    {
        $this->hour = $hour;
        return $this;
    }
    /** Retourne la date/heure complète de la réservation. */
    public function getReservationDate(): ?\DateTimeInterface
    {
        return $this->reservationDate;
    }
    /**
     * Définit la date de réservation et synchronise date + heure automatiquement.
     * C'est la logique métier principale : un seul setter met à jour les 3 champs.
     */
    public function setReservationDate(\DateTimeInterface $reservationDate): self
    {
        $dateTime = $reservationDate instanceof \DateTime
            ? $reservationDate
            : \DateTime::createFromInterface($reservationDate);
        $this->reservationDate = $dateTime;
        $this->date = $dateTime;
        $this->hour = $dateTime->format('H:i');
        return $this;
    }
    /** Retourne la durée du massage réservé. */
    public function getDuration(): ?string
    {
        return $this->duration;
    }
    /** Définit la durée du massage réservé. */
    public function setDuration(?string $duration): self
    {
        $this->duration = $duration;
        return $this;
    }
    /** Retourne le prix de la réservation. */
    public function getPrice(): ?int
    {
        return $this->price;
    }
    /** Définit le prix de la réservation. */
    public function setPrice(?int $price): self
    {
        $this->price = $price;
        return $this;
    }
    /** Retourne le statut sous forme d'enum ReservationStatus. */
    public function getStatus(): ReservationStatus
    {
        return ReservationStatus::from($this->status);
    }
    /** Définit le statut de la réservation. */
    public function setStatus(ReservationStatus $status): self
    {
        $this->status = $status->value;
        return $this;
    }
    /**
     * Retourne les chats masseurs assignés à cette réservation.
     *
     * @return Collection<int, Cat>
     */
    public function getCats(): Collection
    {
        return $this->cats;
    }
    /** Ajoute un chat masseur à la réservation (sans doublon). */
    public function addCat(Cat $cat): self
    {
        if (!$this->cats->contains($cat)) {
            $this->cats->add($cat);
        }
        return $this;
    }
}
