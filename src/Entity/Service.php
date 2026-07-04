<?php
namespace App\Entity;
use App\Repository\ServiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
/**
 * Entité Service : représente une prestation de massage proposée dans les salons.
 * Une prestation a un titre, une description, une durée, un prix,
 * et peut être réalisée par plusieurs chats masseurs.
 */
#[ORM\Entity(repositoryClass: ServiceRepository::class)]
#[ORM\Table(name: 'service')]
class Service
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null; // Identifiant unique en base
    #[ORM\Column(type: 'text')]
    private ?string $title = null; // Nom de la prestation (ex : Massage relaxant)
    #[ORM\Column(type: 'text')]
    private ?string $description = null; // Description détaillée de la prestation
    #[ORM\Column(length: 255)]
    private ?string $duration = null; // Durée du massage (ex : "60 min")
    #[ORM\Column(type: 'integer')]
    private ?int $price = null; // Prix en centimes ou euros selon la config
    #[ORM\Column(name: 'is_global', type: 'boolean')]
    private bool $isGlobal = true; // True si la prestation est dispo dans tous les salons
    #[ORM\Column(name: 'is_remote_live_chat', type: 'boolean')]
    private bool $isRemoteLiveChat = false; // Prestation spéciale : live chat à distance pour tous les chats
    /**
     * @var Collection<int, Cat>
     */
    #[ORM\ManyToMany(targetEntity: Cat::class, inversedBy: 'services')]
    #[ORM\JoinTable(name: 'service_cat')]
    #[ORM\JoinColumn(name: 'service_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'cat_id', referencedColumnName: 'id')]
    private Collection $cats; // Chats masseurs capables de faire cette prestation
    /**
     * Initialise la collection vide des chats masseurs.
     */
    public function __construct()
    {
        $this->cats = new ArrayCollection();
    }
    /** Retourne l'identifiant de la prestation. */
    public function getId(): ?int
    {
        return $this->id;
    }
    /** Retourne le titre de la prestation. */
    public function getTitle(): ?string
    {
        return $this->title;
    }
    /** Définit le titre de la prestation. */
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }
    /** Retourne la description de la prestation. */
    public function getDescription(): ?string
    {
        return $this->description;
    }
    /** Définit la description de la prestation. */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }
    /** Retourne la durée du massage. */
    public function getDuration(): ?string
    {
        return $this->duration;
    }
    /** Définit la durée du massage. */
    public function setDuration(string $duration): self
    {
        $this->duration = $duration;
        return $this;
    }
    /** Retourne le prix de la prestation. */
    public function getPrice(): ?int
    {
        return $this->price;
    }
    /** Définit le prix de la prestation. */
    public function setPrice(int $price): self
    {
        $this->price = $price;
        return $this;
    }
    /** Indique si la prestation est disponible globalement (tous salons). */
    public function isGlobal(): bool
    {
        return $this->isGlobal;
    }
    /** Définit si la prestation est globale ou liée à certains salons. */
    public function setIsGlobal(bool $isGlobal): self
    {
        $this->isGlobal = $isGlobal;
        return $this;
    }
    /** Indique si c'est la prestation live chat à distance (proposée par tous les chats). */
    public function isRemoteLiveChat(): bool
    {
        return $this->isRemoteLiveChat;
    }
    /** Définit si la prestation est le live chat à distance. */
    public function setIsRemoteLiveChat(bool $isRemoteLiveChat): self
    {
        $this->isRemoteLiveChat = $isRemoteLiveChat;
        return $this;
    }
    /**
     * Retourne les chats masseurs qui proposent cette prestation.
     *
     * @return Collection<int, Cat>
     */
    public function getCats(): Collection
    {
        return $this->cats;
    }
    /**
     * Associe un chat masseur à la prestation et met à jour la relation inverse.
     */
    public function addCat(Cat $cat): self
    {
        if (!$this->cats->contains($cat)) {
            $this->cats->add($cat);
            $cat->addService($this);
        }
        return $this;
    }
    /**
     * Retire un chat masseur de la prestation et met à jour la relation inverse.
     */
    public function removeCat(Cat $cat): self
    {
        if ($this->cats->removeElement($cat)) {
            $cat->removeService($this);
        }
        return $this;
    }
}
