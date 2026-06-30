<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')] // Force l'utilisation de votre table existante
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $firstname = null;

    #[ORM\Column(length: 255, unique: true)] // L'email doit être unique pour la connexion
    private ?string $email = null;

    #[ORM\Column(type: 'text')]
    private ?string $password = null;

    #[ORM\Column(nullable: true)]
    private ?int $birthdate = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $role = null;

    #[ORM\Column(name: 'subscribe_id', nullable: true)]
    private ?int $subscribeId = null;

    // Métadonnées requises par Symfony Security
    public function getId(): ?int { return $this->id; }
    public function getName(): ?string { return $this->name; }
    public function setName(?string $name): self { $this->name = $name; return $this; }
    public function getFirstname(): ?string { return $this->firstname; }
    public function setFirstname(?string $firstname): self { $this->firstname = $firstname; return $this; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }
    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): self { $this->password = $password; return $this; }
    public function getBirthdate(): ?int { return $this->birthdate; }
    public function setBirthdate(?int $birthdate): self { $this->birthdate = $birthdate; return $this; }
    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $phone): self { $this->phone = $phone; return $this; }
    
    // Correspondance avec le système de rôles natif de Symfony
    public function getRole(): ?string { return $this->role; }
    public function setRole(?string $role): self { $this->role = $role; return $this; }

    /**
     * Identifiant unique de connexion (Symfony Requirement)
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * Retourne les rôles sous forme de tableau requis par Symfony
     */
    public function getRoles(): array
    {
        // On adapte votre chaîne brute en tableau exploitable par Symfony
        $roles = $this->role ? [$this->role] : [];
        // Garantit que chaque utilisateur a au moins le rôle de base
        $roles[] = 'ROLE_USER'; 

        return array_unique($roles);
    }

    public function eraseCredentials(): void
    {
        // Utile si vous stockez des données sensibles temporaires sur l'objet
    }
}