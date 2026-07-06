<?php
namespace App\Command;
use App\Entity\Location;
use App\Entity\Manager;
use App\Entity\User;
use App\Enum\UserRole;
use App\Repository\LocationRepository;
use App\Repository\ManagerRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
/**
 * Commande console : initialise le compte admin global au premier lancement.
 * Lit l'email et le mot de passe depuis les variables d'environnement.
 */
#[AsCommand(
    name: 'app:create-admin-user',
    description: 'Crée le compte administrateur global, sa localisation et son profil manager',
)]
final class CreateAdminUserCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ManagerRepository $managerRepository,
        private readonly LocationRepository $locationRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        #[Autowire('%env(ADMIN_EMAIL)%')]
        private readonly string $adminEmail,
        #[Autowire('%env(ADMIN_PASSWORD)%')]
        private readonly string $adminPassword,
    ) {
        parent::__construct();
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        // Si la localisation globale existe déjà, tout a été créé avant
        $globalLocation = $this->locationRepository->findGlobalLocation();
        if ($globalLocation !== null) {
            $io->success('La localisation globale et le compte administrateur existent déjà.');
            return Command::SUCCESS;
        }
        // Création ou mise à jour de l'utilisateur admin
        $admin = $this->userRepository->findOneByEmail($this->adminEmail);
        if (!$admin instanceof User) {
            $admin = new User();
            $admin->setEmail($this->adminEmail);
            $admin->setName('Admin');
            $admin->setFirstname('Super');
            $admin->setUserRole(UserRole::Manager);
            $admin->setPassword($this->passwordHasher->hashPassword($admin, $this->adminPassword));
            $admin->setIsEmailVerified(true);
            $this->entityManager->persist($admin);
        } else {
            $admin->setUserRole(UserRole::Manager);
            $admin->setIsEmailVerified(true);
        }
        // Profil Manager avec le flag isAdmin pour les droits globaux
        $manager = $this->managerRepository->findOneByUser($admin);
        if ($manager === null) {
            $manager = new Manager();
            $manager->setUser($admin);
            $manager->setIsAdmin(true);
            $this->entityManager->persist($manager);
        } else {
            $manager->setIsAdmin(true);
        }
        // Localisation « globale » (plateforme centrale, pas une ville)
        $globalLocation = new Location();
        $globalLocation->setCity(Location::GLOBAL_CITY);
        $globalLocation->setAddress('Plateforme centrale');
        $globalLocation->setCountry('France');
        $globalLocation->setIsGlobal(true);
        $this->entityManager->persist($globalLocation);
        $manager->setLocation($globalLocation);
        $this->entityManager->flush();
        $io->success(sprintf(
            'Administrateur global créé (%s) avec la localisation globale.',
            $this->adminEmail
        ));
        return Command::SUCCESS;
    }
}
