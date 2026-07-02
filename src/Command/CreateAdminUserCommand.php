<?php

namespace App\Command;

use App\Entity\User;
use App\Enum\UserRole;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin-user',
    description: 'Crée le compte administrateur par défaut s\'il n\'existe pas encore',
)]
final class CreateAdminUserCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
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

        if ($this->userRepository->findOneBy(['email' => $this->adminEmail]) instanceof User) {
            $io->success(sprintf('Le compte administrateur "%s" existe déjà.', $this->adminEmail));

            return Command::SUCCESS;
        }

        $admin = new User();
        $admin->setEmail($this->adminEmail);
        $admin->setName('Admin');
        $admin->setFirstname('Super');
        $admin->setUserRole(UserRole::Admin);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, $this->adminPassword));

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $io->success(sprintf('Compte administrateur créé : %s', $this->adminEmail));

        return Command::SUCCESS;
    }
}
