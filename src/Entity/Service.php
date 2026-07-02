<?php

namespace App\Entity;

use App\Repository\ServiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ServiceRepository::class)]
#[ORM\Table(name: 'service')]
class Service
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $duration = null;

    #[ORM\Column(type: 'integer')]
    private ?int $price = null;

    #[ORM\Column(name: 'is_global', type: 'boolean')]
    private bool $isGlobal = true;

    /**
     * @var Collection<int, Cat>
     */
    #[ORM\ManyToMany(targetEntity: Cat::class, inversedBy: 'services')]
    #[ORM\JoinTable(name: 'service_cat')]
    #[ORM\JoinColumn(name: 'service_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'cat_id', referencedColumnName: 'id')]
    private Collection $cats;

    public function __construct()
    {
        $this->cats = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDuration(): ?string
    {
        return $this->duration;
    }

    public function setDuration(string $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function isGlobal(): bool
    {
        return $this->isGlobal;
    }

    public function setIsGlobal(bool $isGlobal): self
    {
        $this->isGlobal = $isGlobal;

        return $this;
    }

    /**
     * @return Collection<int, Cat>
     */
    public function getCats(): Collection
    {
        return $this->cats;
    }

    public function addCat(Cat $cat): self
    {
        if (!$this->cats->contains($cat)) {
            $this->cats->add($cat);
            $cat->addService($this);
        }

        return $this;
    }

    public function removeCat(Cat $cat): self
    {
        if ($this->cats->removeElement($cat)) {
            $cat->removeService($this);
        }

        return $this;
    }
}

