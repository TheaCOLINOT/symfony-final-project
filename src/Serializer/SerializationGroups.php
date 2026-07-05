<?php

namespace App\Serializer;

/**
 * Groupes de normalisation / dénormalisation pour l'API JSON.
 */
final class SerializationGroups
{
    public const API_READ = 'api:read';
    public const API_READ_DETAIL = 'api:read:detail';
    public const API_WRITE = 'api:write';

    private function __construct()
    {
    }
}
