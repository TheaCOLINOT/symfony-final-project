<?php

namespace App\DataFixtures;

use App\Entity\Cat;
use App\Entity\LiveChatMessage;
use App\Entity\Location;
use App\Entity\Manager;
use App\Entity\Reservation;
use App\Entity\Service;
use App\Entity\User;
use App\Enum\ReservationStatus;
use App\Enum\UserRole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Jeu de données de démonstration pour tester l'application immédiatement.
 *
 * Comptes fixes documentés dans README.md — mot de passe commun de démo : voir README.
 */
final class AppFixtures extends Fixture implements FixtureGroupInterface
{
  public const GROUP_DEMO = 'demo';

  private Generator $faker;

  public function __construct(
      private readonly UserPasswordHasherInterface $passwordHasher,
  ) {
      $this->faker = Factory::create('fr_FR');
      $this->faker->seed(2026);
  }

  public static function getGroups(): array
  {
      return [self::GROUP_DEMO];
  }

  public function load(ObjectManager $manager): void
  {
      // --- Localisations ---
      $globalLocation = $this->createLocation('Plateforme centrale', 'France', Location::GLOBAL_CITY, isGlobal: true);
      $remoteLocation = $this->createLocation('En ligne', 'France', Location::REMOTE_CITY, isRemote: true);
      $paris = $this->createLocation('12 Rue de Rivoli', 'France', 'Paris');
      $lyon = $this->createLocation('5 Place Bellecour', 'France', 'Lyon');
      $marseille = $this->createLocation('22 La Canebière', 'France', 'Marseille');

      foreach ([$globalLocation, $remoteLocation, $paris, $lyon, $marseille] as $location) {
          $manager->persist($location);
      }

      // --- Utilisateurs fixes (identifiants documentés dans README.md) ---
      $admin = $this->createUser('admin@salon.local', 'Admin123!', 'Super', 'Admin', UserRole::Manager);
      $client = $this->createUser('client@demo.local', 'Client123!', 'Léa', 'Martin', UserRole::User, '0612345678');
      $managerParis = $this->createUser('manager.paris@demo.local', 'Manager123!', 'Marc', 'Dupont', UserRole::Manager);
      $managerLyon = $this->createUser('manager.lyon@demo.local', 'Manager123!', 'Sophie', 'Bernard', UserRole::Manager);
      $catSiam = $this->createUser('chat.siam@demo.local', 'Cat123!', 'Mistigri', 'Siamois', UserRole::Cat);
      $catPersan = $this->createUser('chat.persan@demo.local', 'Cat123!', 'Pompon', 'Persan', UserRole::Cat);

      // Client supplémentaire généré par Faker
      $clientExtra = $this->createUser(
          'client.extra@demo.local',
          'Client123!',
          $this->faker->firstName(),
          $this->faker->lastName(),
          UserRole::User,
          $this->faker->phoneNumber(),
      );

      foreach ([$admin, $client, $managerParis, $managerLyon, $catSiam, $catPersan, $clientExtra] as $user) {
          $manager->persist($user);
      }

      // --- Managers ---
      $adminManager = (new Manager())
          ->setUser($admin)
          ->setIsAdmin(true)
          ->setLocation($globalLocation);
      $parisManager = (new Manager())
          ->setUser($managerParis)
          ->setLocation($paris);
      $lyonManager = (new Manager())
          ->setUser($managerLyon)
          ->setLocation($lyon);

      $globalLocation->addManager($adminManager);
      $paris->addManager($parisManager);
      $lyon->addManager($lyonManager);

      foreach ([$adminManager, $parisManager, $lyonManager] as $managerEntity) {
          $manager->persist($managerEntity);
      }

      // --- Prestations ---
      $relaxant = $this->createService(
          'Massage relaxant',
          'Massage doux aux huiles essentielles pour évacuer le stress.',
          '60 min',
          45,
      );
      $sportif = $this->createService(
          'Massage sportif',
          'Massage tonique pour récupérer après l\'effort.',
          '45 min',
          55,
      );
      $pierres = $this->createService(
          'Massage aux pierres chaudes',
          'Chaleur des pierres de basalte pour une détente profonde.',
          '75 min',
          65,
      );
      $liveChat = $this->createService(
          'Live chat avec masseur chat',
          'Prestation à distance : échangez en direct avec votre masseur chat.',
          '30 min',
          15,
          isRemoteLiveChat: true,
      );

      foreach ([$relaxant, $sportif, $pierres, $liveChat] as $service) {
          $manager->persist($service);
      }

      // --- Chats masseurs ---
      $siamois = (new Cat())
          ->setSpecie('Siamois')
          ->setColor('Chocolat et crème')
          ->setSpeciality('Massage relaxant')
          ->setUser($catSiam);
      $persan = (new Cat())
          ->setSpecie('Persan')
          ->setColor('Blanc et gris')
          ->setSpeciality('Massage sportif')
          ->setUser($catPersan);

      $manager->persist($siamois);
      $manager->persist($persan);

      // Salons
      $siamois->addLocation($paris)->addLocation($lyon);
      $persan->addLocation($paris);

      // Prestations proposées
      $siamois->addService($relaxant)->addService($pierres);
      $persan->addService($relaxant)->addService($sportif)->addService($liveChat);

      $relaxant->addCat($siamois)->addCat($persan);
      $sportif->addCat($persan);
      $pierres->addCat($siamois);
      $liveChat->addCat($persan);

      // --- Réservations ---
      $pastParis = $this->createReservation(
          $client,
          $relaxant,
          $paris,
          $siamois,
          new \DateTimeImmutable('-5 days 14:00'),
      );
      $upcomingLyon = $this->createReservation(
          $client,
          $relaxant,
          $lyon,
          $siamois,
          new \DateTimeImmutable('+4 days 10:30'),
      );
      $upcomingParis = $this->createReservation(
          $clientExtra,
          $pierres,
          $paris,
          $siamois,
          new \DateTimeImmutable('+2 days 16:00'),
      );
      $remoteChat = $this->createReservation(
          $client,
          $liveChat,
          $remoteLocation,
          $persan,
          new \DateTimeImmutable('-1 hour'),
      );

      foreach ([$pastParis, $upcomingLyon, $upcomingParis, $remoteChat] as $reservation) {
          $manager->persist($reservation);
      }

      // Messages live chat
      $messages = [
          [LiveChatMessage::SENDER_USER, 'Bonjour, j\'ai mal au dos !'],
          [LiveChatMessage::SENDER_CAT, 'miaou mrrr pat pat'],
          [LiveChatMessage::SENDER_USER, 'Merci Mistigri !'],
          [LiveChatMessage::SENDER_CAT, 'prrrrrr'],
      ];

      $baseTime = new \DateTimeImmutable('-55 minutes');
      foreach ($messages as $index => [$sender, $content]) {
          $message = (new LiveChatMessage())
              ->setReservation($remoteChat)
              ->setSender($sender)
              ->setContent($content)
              ->setCreatedAt($baseTime->modify(sprintf('+%d minutes', $index * 3)));

          $manager->persist($message);
      }

      $manager->flush();
  }

  private function createUser(
      string $email,
      string $plainPassword,
      string $firstname,
      string $name,
      UserRole $role,
      ?string $phone = null,
  ): User {
      $user = new User();
      $user->setEmail($email);
      $user->setFirstname($firstname);
      $user->setName($name);
      $user->setPhone($phone);
      $user->setUserRole($role);
      $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
      $user->setIsEmailVerified(true);

      return $user;
  }

  private function createLocation(
      string $address,
      string $country,
      string $city,
      bool $isGlobal = false,
      bool $isRemote = false,
  ): Location {
      $location = new Location();
      $location->setAddress($address);
      $location->setCountry($country);
      $location->setCity($city);
      $location->setIsGlobal($isGlobal);
      $location->setIsRemote($isRemote);

      return $location;
  }

  private function createService(
      string $title,
      string $description,
      string $duration,
      int $price,
      bool $isRemoteLiveChat = false,
  ): Service {
      $service = new Service();
      $service->setTitle($title);
      $service->setDescription($description);
      $service->setDuration($duration);
      $service->setPrice($price);
      $service->setIsGlobal(true);
      $service->setIsRemoteLiveChat($isRemoteLiveChat);

      return $service;
  }

  private function createReservation(
      User $user,
      Service $service,
      Location $location,
      Cat $cat,
      \DateTimeImmutable $at,
  ): Reservation {
      $reservation = new Reservation();
      $reservation->setUser($user);
      $reservation->setService($service);
      $reservation->setLocation($location);
      $reservation->setReservationDate($at);
      $reservation->setServiceLabel($service->getTitle());
      $reservation->setCatLabel(sprintf(
          '%s (%s)',
          $cat->getSpeciality() ?? 'Masseur',
          $cat->getSpecie() ?? 'chat',
      ));
      $reservation->setDuration($service->getDuration());
      $reservation->setPrice($service->getPrice());
      $reservation->setStatus(ReservationStatus::Confirmed);
      $reservation->addCat($cat);

      return $reservation;
  }
}
