<?php
namespace App\Entity;
use App\Repository\LocationRepository;
use App\Serializer\SerializationGroups;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
/**
 * Entité Location : représente un salon de massage (ou une localisation globale).
 * Un salon a une adresse, une ville, un pays, et accueille des chats masseurs
 * et des managers qui le gèrent.
 */
#[ORM\Entity(repositoryClass: LocationRepository::class)]
#[ORM\Table(name: 'location')]
class Location
{
    public const GLOBAL_CITY = 'Global'; // Nom de ville pour la localisation globale (tous salons)
    public const REMOTE_CITY = 'À distance'; // Libellé pour les prestations en ligne
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups([SerializationGroups::API_READ])]
    private ?int $id = null; // Identifiant unique en base
    #[ORM\Column(type: 'text')]
    #[Groups([SerializationGroups::API_READ])]
    private ?string $address = null; // Adresse postale du salon
    #[ORM\Column(type: 'text')]
    #[Groups([SerializationGroups::API_READ])]
    private ?string $country = null; // Pays du salon
    #[ORM\Column(type: 'text')]
    #[Groups([SerializationGroups::API_READ])]
    private ?string $city = null; // Ville du salon
    #[ORM\Column(name: 'is_global', type: 'boolean')]
    private bool $isGlobal = false; // True si c'est la localisation globale (pas un salon physique)
    #[ORM\Column(name: 'is_remote', type: 'boolean')]
    #[Groups([SerializationGroups::API_READ])]
    private bool $isRemote = false; // True pour le lieu virtuel "À distance" (live chat)
    /**
     * @var Collection<int, Manager>
     */
    #[ORM\OneToMany(mappedBy: 'location', targetEntity: Manager::class)]
    private Collection $managers; // Managers responsables de ce salon
    /**
     * @var Collection<int, Cat>
     */
    #[ORM\ManyToMany(targetEntity: Cat::class, mappedBy: 'locations')]
    private Collection $cats; // Chats masseurs qui travaillent dans ce salon
    /**
     * Initialise les collections vides pour les relations.
     */
    public function __construct()
    {
        $this->managers = new ArrayCollection();
        $this->cats = new ArrayCollection();
    }
    /** Retourne l'identifiant du salon. */
    public function getId(): ?int
    {
        return $this->id;
    }
    /** Retourne l'adresse du salon. */
    public function getAddress(): ?string
    {
        return $this->address;
    }
    /** Définit l'adresse du salon. */
    public function setAddress(string $address): self
    {
        $this->address = $address;
        return $this;
    }
    /** Retourne le pays du salon. */
    public function getCountry(): ?string
    {
        return $this->country;
    }
    /** Définit le pays du salon. */
    public function setCountry(string $country): self
    {
        $this->country = $country;
        return $this;
    }
    /** Retourne la ville du salon. */
    public function getCity(): ?string
    {
        return $this->city;
    }
    /** Définit la ville du salon. */
    public function setCity(string $city): self
    {
        $this->city = $city;
        return $this;
    }
    /**
     * Retourne le premier manager du salon (raccourci pratique).
     */
    public function getManager(): ?Manager
    {
        return $this->managers->first() ?: null;
    }
    /**
     * Retourne tous les managers de ce salon.
     *
     * @return Collection<int, Manager>
     */
    public function getManagers(): Collection
    {
        return $this->managers;
    }
    /**
     * Ajoute un manager au salon et met à jour la relation inverse.
     */
    public function addManager(Manager $manager): self
    {
        if (!$this->managers->contains($manager)) {
            $this->managers->add($manager);
            $manager->setLocation($this);
        }
        return $this;
    }
    /**
     * Retire un manager du salon et détache sa localisation si besoin.
     */
    public function removeManager(Manager $manager): self
    {
        if ($this->managers->removeElement($manager)) {
            if ($manager->getLocation() === $this) {
                $manager->setLocation(null);
            }
        }
        return $this;
    }
    /** Indique si c'est la localisation globale (pas un salon physique). */
    public function isGlobal(): bool
    {
        return $this->isGlobal;
    }
    /** Définit si c'est une localisation globale ou un salon physique. */
    public function setIsGlobal(bool $isGlobal): self
    {
        $this->isGlobal = $isGlobal;
        return $this;
    }
    /** Indique si c'est le lieu virtuel des prestations à distance. */
    public function isRemote(): bool
    {
        return $this->isRemote;
    }
    /** Définit si la localisation est virtuelle (à distance). */
    public function setIsRemote(bool $isRemote): self
    {
        $this->isRemote = $isRemote;
        return $this;
    }
    /**
     * Retourne les chats masseurs affectés à ce salon.
     *
     * @return Collection<int, Cat>
     */
    public function getCats(): Collection
    {
        return $this->cats;
    }
    /**
     * Ajoute un chat masseur au salon (délègue à Cat::addLocation).
     */
    public function addCat(Cat $cat): self
    {
        return $cat->addLocation($this);
    }
    /**
     * Retire un chat masseur du salon (délègue à Cat::removeLocation).
     */
    public function removeCat(Cat $cat): self
    {
        return $cat->removeLocation($this);
    }
    /**
     * Retourne un libellé lisible pour l'affichage (ville + adresse ou "globale").
     */
    public function getDisplayName(): string
    {
        if ($this->isGlobal) {
            return 'Localisation globale';
        }
        return sprintf('%s — %s', $this->city, $this->address);
    }
}
