<?php
namespace App\Entity;
use App\Repository\CatRepository;
use App\Serializer\SerializationGroups;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
/**
 * Entité Cat : représente un chat masseur du réseau de salons.
 * Chaque chat a une espèce, une couleur, une spécialité, et peut travailler
 * dans plusieurs salons (Location) en proposant différentes prestations (Service).
 */
#[ORM\Entity(repositoryClass: CatRepository::class)]
#[ORM\Table(name: 'cat')]
class Cat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups([SerializationGroups::API_READ])]
    private ?int $id = null; // Identifiant unique en base
    #[ORM\Column(type: 'text')]
    #[Groups([SerializationGroups::API_READ])]
    private ?string $specie = null; // Espèce du chat (ex : Siamois, Maine Coon)
    #[ORM\Column(type: 'text')]
    #[Groups([SerializationGroups::API_READ])]
    private ?string $color = null; // Couleur du pelage
    #[ORM\Column(type: 'text')]
    #[Groups([SerializationGroups::API_READ])]
    private ?string $speciality = null; // Spécialité de massage (ex : relaxant, sportif)
    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, unique: true)]
    private ?User $user = null; // Compte utilisateur lié au chat masseur (pour se connecter)
    /**
     * @var Collection<int, Location>
     */
    #[ORM\ManyToMany(targetEntity: Location::class, inversedBy: 'cats')]
    #[ORM\JoinTable(name: 'cat_location')]
    private Collection $locations; // Salons où ce chat masseur intervient
    /**
     * @var Collection<int, Service>
     */
    #[ORM\ManyToMany(targetEntity: Service::class, mappedBy: 'cats')]
    private Collection $services; // Prestations que ce chat sait réaliser
    /**
     * Initialise les collections vides pour les relations ManyToMany.
     */
    public function __construct()
    {
        $this->locations = new ArrayCollection();
        $this->services = new ArrayCollection();
    }
    /** Retourne l'identifiant du chat masseur. */
    public function getId(): ?int
    {
        return $this->id;
    }
    /** Retourne l'espèce du chat. */
    public function getSpecie(): ?string
    {
        return $this->specie;
    }
    /** Définit l'espèce du chat. */
    public function setSpecie(string $specie): self
    {
        $this->specie = $specie;
        return $this;
    }
    /** Retourne la couleur du chat. */
    public function getColor(): ?string
    {
        return $this->color;
    }
    /** Définit la couleur du chat. */
    public function setColor(string $color): self
    {
        $this->color = $color;
        return $this;
    }
    /** Retourne la spécialité de massage du chat. */
    public function getSpeciality(): ?string
    {
        return $this->speciality;
    }
    /** Définit la spécialité de massage du chat. */
    public function setSpeciality(string $speciality): self
    {
        $this->speciality = $speciality;
        return $this;
    }
    /** Retourne le compte utilisateur associé au chat masseur. */
    public function getUser(): ?User
    {
        return $this->user;
    }
    /** Associe un compte utilisateur au chat masseur. */
    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }
    /**
     * Retourne la liste des salons où travaille ce chat.
     *
     * @return Collection<int, Location>
     */
    public function getLocations(): Collection
    {
        return $this->locations;
    }
    /**
     * Ajoute un salon au chat et met à jour la relation inverse côté Location.
     */
    public function addLocation(Location $location): self
    {
        if (!$this->locations->contains($location)) {
            $this->locations->add($location);
        }
        if (!$location->getCats()->contains($this)) {
            $location->getCats()->add($this);
        }
        return $this;
    }
    /**
     * Retire un salon du chat et met à jour la relation inverse côté Location.
     */
    public function removeLocation(Location $location): self
    {
        $this->locations->removeElement($location);
        $location->getCats()->removeElement($this);
        return $this;
    }
    /** Vérifie si le chat est affecté à au moins un salon. */
    public function hasLocations(): bool
    {
        return !$this->locations->isEmpty();
    }
    /** Vérifie si le chat travaille dans un salon donné. */
    public function isInLocation(Location $location): bool
    {
        return $this->locations->contains($location);
    }
    /**
     * Retourne les prestations proposées par ce chat masseur.
     *
     * @return Collection<int, Service>
     */
    public function getServices(): Collection
    {
        return $this->services;
    }
    /**
     * Associe une prestation au chat et met à jour la relation inverse côté Service.
     */
    public function addService(Service $service): self
    {
        if (!$this->services->contains($service)) {
            $this->services->add($service);
            $service->addCat($this);
        }
        return $this;
    }
    /**
     * Retire une prestation du chat et met à jour la relation inverse côté Service.
     */
    public function removeService(Service $service): self
    {
        if ($this->services->removeElement($service)) {
            $service->removeCat($this);
        }
        return $this;
    }
}
