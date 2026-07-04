<?php
namespace App\Entity;
use App\Enum\UserRole;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
/**
 * Entité User : représente un utilisateur du réseau de salons de massage.
 * Peut être un client, un manager, un chat masseur ou un admin selon son rôle.
 * Implémente les interfaces Symfony Security pour l'authentification.
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')] // Force l'utilisation de votre table existante
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null; // Identifiant unique en base
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $name = null; // Nom de famille
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $firstname = null; // Prénom
    #[ORM\Column(length: 255, unique: true)] // L'email doit être unique pour la connexion
    private ?string $email = null; // Adresse email (identifiant de connexion)
    #[ORM\Column(type: 'text')]
    private ?string $password = null; // Mot de passe hashé
    #[ORM\Column(nullable: true)]
    private ?int $birthdate = null; // Date de naissance (timestamp ou format stocké en int)
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null; // Numéro de téléphone
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $role = null; // Rôle stocké en chaîne (ROLE_USER, ROLE_CAT, etc.)
    #[ORM\Column(name: 'subscribe_id', nullable: true)]
    private ?int $subscribeId = null; // Identifiant d'abonnement éventuel
    /** Retourne l'identifiant de l'utilisateur. */
    public function getId(): ?int { return $this->id; }
    /** Retourne le nom de famille. */
    public function getName(): ?string { return $this->name; }
    /** Définit le nom de famille. */
    public function setName(?string $name): self { $this->name = $name; return $this; }
    /** Retourne le prénom. */
    public function getFirstname(): ?string { return $this->firstname; }
    /** Définit le prénom. */
    public function setFirstname(?string $firstname): self { $this->firstname = $firstname; return $this; }
    /** Retourne l'email de l'utilisateur. */
    public function getEmail(): ?string { return $this->email; }
    /** Définit l'email (utilisé pour la connexion). */
    public function setEmail(string $email): self { $this->email = $email; return $this; }
    /** Retourne le mot de passe hashé. */
    public function getPassword(): ?string { return $this->password; }
    /** Définit le mot de passe (doit être hashé avant). */
    public function setPassword(string $password): self { $this->password = $password; return $this; }
    /** Retourne la date de naissance. */
    public function getBirthdate(): ?int { return $this->birthdate; }
    /** Définit la date de naissance. */
    public function setBirthdate(?int $birthdate): self { $this->birthdate = $birthdate; return $this; }
    /** Retourne le numéro de téléphone. */
    public function getPhone(): ?string { return $this->phone; }
    /** Définit le numéro de téléphone. */
    public function setPhone(?string $phone): self { $this->phone = $phone; return $this; }
    /** Retourne le rôle brut stocké en base (chaîne). */
    public function getRole(): ?string
    {
        return $this->role;
    }
    /** Définit le rôle brut en base (chaîne). */
    public function setRole(?string $role): self
    {
        $this->role = $role;
        return $this;
    }
    /** Convertit le rôle stocké en enum UserRole (plus pratique en PHP). */
    public function getUserRole(): ?UserRole
    {
        return UserRole::fromString($this->role);
    }
    /** Définit le rôle via l'enum UserRole. */
    public function setUserRole(UserRole $role): self
    {
        $this->role = $role->value;
        return $this;
    }
    /** Vérifie si l'utilisateur possède un rôle donné. */
    public function hasRole(UserRole $role): bool
    {
        return $this->role === $role->value;
    }
    /**
     * Identifiant unique de connexion (Symfony Requirement)
     * Retourne l'email comme identifiant pour l'authentification.
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }
    /**
     * Retourne les rôles Symfony pour la sécurité.
     * Par défaut tout le monde a au moins ROLE_USER.
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        if ($this->role === null || $this->role === UserRole::User->value) {
            return [UserRole::User->value];
        }
        return [$this->role];
    }
    /**
     * Efface les données sensibles temporaires après authentification.
     * Requis par l'interface UserInterface de Symfony.
     */
    public function eraseCredentials(): void
    {
        // Utile si vous stockez des données sensibles temporaires sur l'objet
    }
}
