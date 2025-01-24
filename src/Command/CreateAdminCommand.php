<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Creates a new admin user'
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = new User();
        $user->setEmail('mariusz005@o2.pl');
        $user->setUsername('mariusz005');
        $user->setFirstName('Mariusz');
        $user->setLastName('Admin');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setGender('M');
        $user->setSeekingGender('F');
        $user->setLocation('Administrator');
        $user->setBirthDate(new \DateTime('1990-01-01'));
        $user->setIsActive(true);
        $user->setIsBanned(false);
        $user->setForcePasswordChange(false);
        $user->setCreatedAt(new \DateTime());
        $user->setUpdatedAt(new \DateTime());

        // Ustawienie silnego hasła
        $plaintextPassword = 'Admin123!@#';
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plaintextPassword);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln('Admin user created successfully!');
        $output->writeln('Email: mariusz005@o2.pl');
        $output->writeln('Password: ' . $plaintextPassword);

        return Command::SUCCESS;
    }
}
