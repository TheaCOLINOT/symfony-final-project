<?php
namespace App\Enum;
/**
 * Enum UserRole : liste les rôles possibles des utilisateurs de l'application.
 * Chaque rôle correspond à un type d'acteur du réseau de salons de massage
 * (client, manager, chat masseur, administrateur).
 */
enum UserRole: string
{
    case User = 'ROLE_USER'; // Client qui réserve des massages
    case Manager = 'ROLE_MANAGER'; // Responsable d'un salon
    case Cat = 'ROLE_CAT'; // Chat masseur qui réalise les prestations
    case Admin = 'ROLE_ADMIN'; // Administrateur avec tous les droits
    /**
     * Retourne un libellé lisible en français pour afficher le rôle.
     */
    public function label(): string
    {
        return match ($this) {
            self::User => 'Utilisateur standard',
            self::Manager => 'Manager',
            self::Cat => 'Chat masseur',
            self::Admin => 'Administrateur',
        };
    }
    /**
     * Retourne la liste de toutes les valeurs de rôles (pour les formulaires, etc.).
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $role) => $role->value, self::cases());
    }
    /**
     * Convertit une chaîne stockée en base vers l'enum, ou null si invalide.
     */
    public static function fromString(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }
        return self::tryFrom($value);
    }
}
