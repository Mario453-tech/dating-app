<?php

namespace App\Controller;

use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use App\Form\ResetPasswordRequestType;
use App\Form\ResetPasswordType;
use App\Repository\ResetPasswordRequestRepository;
use App\Repository\UserRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class ResetPasswordController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private ResetPasswordRequestRepository $resetPasswordRequestRepository;
    private UserPasswordHasherInterface $passwordHasher;
    private TokenGeneratorInterface $tokenGenerator;
    private EmailService $emailService;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        ResetPasswordRequestRepository $resetPasswordRequestRepository,
        UserPasswordHasherInterface $passwordHasher,
        TokenGeneratorInterface $tokenGenerator,
        EmailService $emailService,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->resetPasswordRequestRepository = $resetPasswordRequestRepository;
        $this->passwordHasher = $passwordHasher;
        $this->tokenGenerator = $tokenGenerator;
        $this->emailService = $emailService;
        $this->logger = $logger;
    }

    #[Route('/reset-password', name: 'app_forgot_password_request')]
    public function request(Request $request): Response
    {
        $form = $this->createForm(ResetPasswordRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $identifier = $form->get('identifier')->getData();
            
            $this->logger->info('Próba resetowania hasła', [
                'identifier' => $identifier
            ]);

            try {
                $user = $this->findUserByIdentifier($identifier);
                $this->processSendingPasswordResetEmail($user);
                
                $this->addFlash('success', 'Jeśli konto o podanym identyfikatorze istnieje, otrzymasz email z instrukcją resetowania hasła.');
                return $this->redirectToRoute('app_login');
            } catch (UserNotFoundException) {
                $this->logger->warning('Próba resetowania hasła dla nieistniejącego użytkownika', [
                    'identifier' => $identifier
                ]);
                $this->addFlash('success', 'Jeśli konto o podanym identyfikatorze istnieje, otrzymasz email z instrukcją resetowania hasła.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function reset(Request $request, ?string $token = null): Response
    {
        if ($token) {
            $resetRequest = $this->entityManager->getRepository(ResetPasswordRequest::class)
                ->findOneBy(['selector' => $token]);

            if (null === $resetRequest) {
                $this->logger->warning('Próba resetowania hasła z nieprawidłowym tokenem', [
                    'token' => $token
                ]);
                throw $this->createNotFoundException('Nieprawidłowy token resetowania hasła.');
            }

            if ($resetRequest->isExpired()) {
                $this->entityManager->remove($resetRequest);
                $this->entityManager->flush();
                
                $this->logger->warning('Próba resetowania hasła z wygasłym tokenem', [
                    'token' => $token
                ]);
                $this->addFlash('reset_password_error', 'Token resetowania hasła wygasł.');
                return $this->redirectToRoute('app_forgot_password_request');
            }
        } else {
            $this->logger->warning('Próba resetowania hasła bez tokenu');
            throw $this->createNotFoundException('Brak tokenu resetowania hasła.');
        }

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $resetRequest->getUser();
            $user->setPassword(
                $this->passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            
            $this->entityManager->remove($resetRequest);
            $this->entityManager->flush();

            $this->logger->info('Hasło zostało zresetowane', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail()
            ]);

            $this->addFlash('success', 'Hasło zostało zmienione.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }

    private function findUserByIdentifier(string $identifier): User
    {
        $user = $this->userRepository->findOneBy(['email' => $identifier])
            ?? $this->userRepository->findOneBy(['username' => $identifier]);

        if (!$user) {
            throw new UserNotFoundException();
        }

        return $user;
    }

    private function processSendingPasswordResetEmail(User $user): void
    {
        // Usuń stare requesty
        $this->resetPasswordRequestRepository->removeExpiredResetRequests();

        // Sprawdź czy nie ma zbyt częstych requestów
        $lastRequestTime = $this->resetPasswordRequestRepository
            ->getUserMostRecentNonExpiredRequestDate($user);

        if ($lastRequestTime instanceof \DateTimeInterface && $lastRequestTime->modify('+1 hour') > new \DateTime()) {
            $this->logger->warning('Próba resetowania hasła zbyt często', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'last_request' => $lastRequestTime->format('Y-m-d H:i:s')
            ]);
            throw new \Exception('Musisz poczekać godzinę przed kolejną próbą resetowania hasła.');
        }

        $resetRequest = new ResetPasswordRequest();
        $resetRequest->setUser($user);
        
        // Generuj unikalny token
        $selector = bin2hex(random_bytes(20));
        $verifier = bin2hex(random_bytes(20));
        
        $resetRequest->setSelector($selector);
        $resetRequest->setHashedToken(hash('sha256', $verifier));
        
        // Ustaw daty
        $now = new \DateTime();
        $resetRequest->setRequestedAt($now);
        $resetRequest->setExpiresAt((clone $now)->modify('+1 hour'));

        $this->entityManager->persist($resetRequest);
        $this->entityManager->flush();

        $this->logger->info('Email resetowania hasła został wysłany', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail()
        ]);

        $this->emailService->sendPasswordResetEmail(
            $user->getEmail(),
            $selector
        );
    }
}
