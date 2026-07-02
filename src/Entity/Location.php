<?php

namespace App\Entity;

use App\Repository\LocationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LocationRepository::class)]
#[ORM\Table(name: 'location')]
class Location
{
    public const GLOBAL_CITY = 'Global';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    private ?string $address = null;

    #[ORM\Column(type: 'text')]
    private ?string $country = null;

    #[ORM\Column(type: 'text')]
    private ?string $city = null;

    #[ORM\Column(name: 'is_global', type: 'boolean')]
    private bool $isGlobal = false;

    /**
     * @var Collection<int, Manager>
     */
    #[ORM\OneToMany(mappedBy: 'location', targetEntity: Manager::class)]
    private Collection $managers;

    /**
     * @var Collection<int, Cat>
     */
    #[ORM\OneToMany(mappedBy: 'location', targetEntity: Cat::class)]
    private Collection $cats;

    public function __construct()
    {
        $this->managers = new ArrayCollection();
        $this->cats = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getManager(): ?Manager
    {
        return $this->managers->first() ?: null;
    }

    /**
     * @return Collection<int, Manager>
     */
    public function getManagers(): Collection
    {
        return $this->managers;
    }

    public function addManager(Manager $manager): self
    {
        if (!$this->managers->contains($manager)) {
            $this->managers->add($manager);
            $manager->setLocation($this);
        }

        return $this;
    }

    public function removeManager(Manager $manager): self
    {
        if ($this->managers->removeElement($manager)) {
            if ($manager->getLocation() === $this) {
                $manager->setLocation(null);
            }
        }

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
            $cat->setLocation($this);
        }

        return $this;
    }

    public function removeCat(Cat $cat): self
    {
        if ($this->cats->removeElement($cat)) {
            if ($cat->getLocation() === $this) {
                $cat->setLocation(null);
            }
        }

        return $this;
    }

    public function getDisplayName(): string
    {
        if ($this->isGlobal) {
            return 'Localisation globale';
        }

        return sprintf('%s — %s', $this->city, $this->address);
    }
}
