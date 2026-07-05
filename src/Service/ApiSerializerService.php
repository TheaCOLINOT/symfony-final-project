<?php

namespace App\Service;

use App\Serializer\SerializationGroups;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Encapsule le Serializer Symfony avec un contexte API cohérent
 * (groupes, profondeur max, format des dates).
 */
final class ApiSerializerService
{
    public function __construct(
        private readonly SerializerInterface $serializer,
    ) {
    }

    /**
     * @param list<string> $groups
     */
    public function serialize(mixed $data, array $groups = [SerializationGroups::API_READ]): string
    {
        return $this->serializer->serialize(
            $data,
            'json',
            $this->buildNormalizationContext($groups),
        );
    }

    /**
     * @param list<string> $groups
     *
     * @return array<string, mixed>|list<mixed>|scalar|null
     */
    public function normalize(mixed $data, array $groups = [SerializationGroups::API_READ]): mixed
    {
        return $this->serializer->normalize(
            $data,
            null,
            $this->buildNormalizationContext($groups),
        );
    }

    /**
     * @param array<string, mixed> $payload
     * @param class-string $type
     * @param list<string> $groups
     */
    public function denormalize(array $payload, string $type, array $groups = [SerializationGroups::API_WRITE]): mixed
    {
        return $this->serializer->denormalize(
            $payload,
            $type,
            'json',
            $this->buildDenormalizationContext($groups),
        );
    }

    /**
     * @param list<string> $groups
     *
     * @return array<string, mixed>
     */
    private function buildNormalizationContext(array $groups): array
    {
        return [
            AbstractNormalizer::GROUPS => $groups,
            AbstractObjectNormalizer::ENABLE_MAX_DEPTH => true,
            DateTimeNormalizer::FORMAT_KEY => \DateTimeInterface::ATOM,
        ];
    }

    /**
     * @param list<string> $groups
     *
     * @return array<string, mixed>
     */
    private function buildDenormalizationContext(array $groups): array
    {
        return [
            AbstractNormalizer::GROUPS => $groups,
            DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS => true,
        ];
    }
}
