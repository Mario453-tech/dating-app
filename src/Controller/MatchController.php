<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/matches')]
#[IsGranted('ROLE_USER')]
class MatchController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository
    ) {
    }

    #[Route('/', name: 'app_matches')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $matches = $this->userRepository->findMatches($user);
        
        return $this->render('match/index.html.twig', [
            'matches' => $matches
        ]);
    }
}
