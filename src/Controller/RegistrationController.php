<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $userPasswordHasher;
    private EmailService $emailService;
    private RequestStack $requestStack;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $userPasswordHasher,
        EmailService $emailService,
        RequestStack $requestStack
    ) {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->userPasswordHasher = $userPasswordHasher;
        $this->emailService = $emailService;
        $this->requestStack = $requestStack;
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request): Response
    {
        if ($this->getUser()) {
            $this->logger->info('Zalogowany użytkownik próbuje dostać się do strony rejestracji', [
                'user_id' => $this->getUser()->getId()
            ]);
            return $this->redirectToRoute('app_profile');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        try {
            if ($form->isSubmitted() && $form->isValid()) {
                // Sprawdź, czy email już istnieje
                $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
                if ($existingUser) {
                    $this->logger->warning('Próba rejestracji na istniejący email', [
                        'email' => $user->getEmail()
                    ]);
                    $this->addFlash('error', 'Ten adres email jest już zajęty.');
                    return $this->renderRegistrationForm($form);
                }

                // Zakoduj hasło
                $user->setPassword(
                    $this->userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );

                // Ustaw domyślne wartości
                $user->setRoles(['ROLE_USER']);
                $user->setCreatedAt(new \DateTime());
                $user->setUpdatedAt(new \DateTime());
                $user->setIsActive(true);
                $user->setForcePasswordChange(false);

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                $this->logger->info('Użytkownik został zarejestrowany', [
                    'user_id' => $user->getId(),
                    'email' => $user->getEmail()
                ]);

                try {
                    $this->emailService->sendWelcomeEmail($user);
                } catch (\Exception $e) {
                    $this->logger->error('Błąd podczas wysyłania emaila powitalnego', [
                        'error' => $e->getMessage(),
                        'user_id' => $user->getId()
                    ]);
                }

                $this->addFlash('success', 'Rejestracja przebiegła pomyślnie! Możesz się teraz zalogować.');
                return $this->redirectToRoute('app_login');
            }
        } catch (\Exception $e) {
            $this->logger->error('Błąd podczas rejestracji', [
                'error' => $e->getMessage()
            ]);
            $this->addFlash('error', 'Wystąpił błąd podczas rejestracji. Spróbuj ponownie.');
        }

        return $this->renderRegistrationForm($form);
    }

    private function renderRegistrationForm($form): Response
    {
        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView()
        ]);
    }

    private function getFormErrors($form): array
    {
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }
        return $errors;
    }
}
