<?php

namespace App\Enum;

enum UserRole: string
{
    case User = 'ROLE_USER';
    case Manager = 'ROLE_MANAGER';
    case Cat = 'ROLE_CAT';
    case Admin = 'ROLE_ADMIN';

    public function label(): string
    {
        return match ($this) {
            self::User => 'Utilisateur standard',
            self::Manager => 'Manager',
            self::Cat => 'Spécialiste chat',
            self::Admin => 'Administrateur',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $role) => $role->value, self::cases());
    }

    public static function fromString(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }

        return self::tryFrom($value);
    }
}
