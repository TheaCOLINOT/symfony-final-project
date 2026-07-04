<?php
namespace App\Entity;
use App\Repository\ManagerRepository;
use Doctrine\ORM\Mapping as ORM;
/**
 * Entité Manager : représente un responsable de salon de massage.
 * Un manager est lié à un compte User et gère éventuellement un salon (Location).
 * Il peut aussi être admin (droits étendus sur tout le réseau).
 */
#[ORM\Entity(repositoryClass: ManagerRepository::class)]
#[ORM\Table(name: 'manager')]
class Manager
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null; // Identifiant unique en base
    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, unique: true)]
    private ?User $user = null; // Compte utilisateur du manager (pour se connecter)
    #[ORM\Column(name: 'is_admin', type: 'boolean')]
    private bool $isAdmin = false; // True si le manager a les droits administrateur
    #[ORM\ManyToOne(targetEntity: Location::class, inversedBy: 'managers')]
    #[ORM\JoinColumn(name: 'location_id', referencedColumnName: 'id', nullable: true)]
    private ?Location $location = null; // Salon géré par ce manager (null si admin global)
    /** Retourne l'identifiant du manager. */
    public function getId(): ?int
    {
        return $this->id;
    }
    /** Retourne le compte utilisateur associé au manager. */
    public function getUser(): ?User
    {
        return $this->user;
    }
    /** Associe un compte utilisateur au manager. */
    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }
    /** Indique si le manager a les droits administrateur. */
    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }
    /** Définit si le manager est administrateur ou non. */
    public function setIsAdmin(bool $isAdmin): self
    {
        $this->isAdmin = $isAdmin;
        return $this;
    }
    /** Retourne le salon géré par ce manager. */
    public function getLocation(): ?Location
    {
        return $this->location;
    }
    /** Affecte un salon au manager. */
    public function setLocation(?Location $location): self
    {
        $this->location = $location;
        return $this;
    }
}
