<?php

namespace App\Tests\Unit;

/**
 * Petits helpers pour fabriquer des entités dans les tests.
 * Comme les ids sont générés par la BDD, on les force avec la réflexion.
 */
trait TestEntityBuilderTrait
{
    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionClass($entity);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $id);
    }

    private function createUser(int $id = 1): \App\Entity\User
    {
        $user = new \App\Entity\User();
        $user->setEmail('client'.$id.'@test.local');
        $user->setPassword('test');
        $user->setFirstname('Jean');
        $user->setName('Dupont');
        $this->setEntityId($user, $id);

        return $user;
    }

    private function createCat(string $speciality = 'Relaxant'): \App\Entity\Cat
    {
        $cat = new \App\Entity\Cat();
        $cat->setSpecie('Siamois');
        $cat->setColor('Gris');
        $cat->setSpeciality($speciality);

        return $cat;
    }

    private function createSalonLocation(): \App\Entity\Location
    {
        $location = new \App\Entity\Location();
        $location->setCity('Paris');
        $location->setAddress('10 rue du Chat');
        $location->setCountry('France');
        $location->setIsGlobal(false);
        $location->setIsRemote(false);

        return $location;
    }

    private function createRemoteLocation(): \App\Entity\Location
    {
        $location = new \App\Entity\Location();
        $location->setCity(\App\Entity\Location::REMOTE_CITY);
        $location->setAddress('En ligne');
        $location->setCountry('France');
        $location->setIsGlobal(false);
        $location->setIsRemote(true);

        return $location;
    }

    private function createClassicService(): \App\Entity\Service
    {
        $service = new \App\Entity\Service();
        $service->setTitle('Massage relaxant');
        $service->setDescription('Massage classique en salon');
        $service->setDuration('60 min');
        $service->setPrice(50);
        $service->setIsGlobal(true);
        $service->setIsRemoteLiveChat(false);

        return $service;
    }

    private function createRemoteLiveChatService(): \App\Entity\Service
    {
        $service = new \App\Entity\Service();
        $service->setTitle('Live chat avec masseur chat');
        $service->setDescription('Prestation à distance');
        $service->setDuration('30 min');
        $service->setPrice(15);
        $service->setIsGlobal(true);
        $service->setIsRemoteLiveChat(true);

        return $service;
    }
}
