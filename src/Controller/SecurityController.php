<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class SecurityController extends AbstractController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            $this->logger->info('Zalogowany użytkownik próbuje dostać się do strony logowania', [
                'user_id' => $this->getUser()->getId(),
                'redirect_to' => 'app_profile'
            ]);
            return $this->redirectToRoute('app_profile');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        if ($error) {
            $this->logger->warning('Błąd logowania', [
                'error' => $error->getMessage(),
                'error_code' => $error->getCode(),
                'username' => $authenticationUtils->getLastUsername()
            ]);
        }

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }

    #[Route('/zaloguj', name: 'app_login_pl')]
    public function loginPl(): Response
    {
        return $this->redirectToRoute('app_login');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        $this->logger->info('Użytkownik wylogował się', [
            'user_id' => $this->getUser()?->getId()
        ]);
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
