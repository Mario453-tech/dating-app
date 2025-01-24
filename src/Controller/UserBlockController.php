<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserBlockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Log\LoggerInterface;

#[Route('/users')]
#[IsGranted('ROLE_USER')]
class UserBlockController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserBlockRepository $blockRepository,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/{id}/block', name: 'app_user_block', methods: ['POST'])]
    public function block(Request $request, User $blockedUser): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if ($currentUser === $blockedUser) {
            return $this->json([
                'error' => 'Nie możesz zablokować samego siebie'
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($currentUser->hasBlockedUser($blockedUser)) {
            return $this->json([
                'error' => 'Ten użytkownik jest już zablokowany'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $reason = $request->request->get('reason');
            $currentUser->blockUser($blockedUser, $reason);
            
            $this->entityManager->flush();

            $this->logger->info('Użytkownik został zablokowany', [
                'blocker_id' => $currentUser->getId(),
                'blocked_id' => $blockedUser->getId(),
                'reason' => $reason
            ]);

            return $this->json([
                'message' => 'Użytkownik został zablokowany'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Błąd podczas blokowania użytkownika', [
                'blocker_id' => $currentUser->getId(),
                'blocked_id' => $blockedUser->getId(),
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'error' => 'Wystąpił błąd podczas blokowania użytkownika'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/unblock', name: 'app_user_unblock', methods: ['POST'])]
    public function unblock(User $blockedUser): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if (!$currentUser->hasBlockedUser($blockedUser)) {
            return $this->json([
                'error' => 'Ten użytkownik nie jest zablokowany'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $currentUser->unblockUser($blockedUser);
            $this->entityManager->flush();

            $this->logger->info('Użytkownik został odblokowany', [
                'blocker_id' => $currentUser->getId(),
                'blocked_id' => $blockedUser->getId()
            ]);

            return $this->json([
                'message' => 'Użytkownik został odblokowany'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Błąd podczas odblokowywania użytkownika', [
                'blocker_id' => $currentUser->getId(),
                'blocked_id' => $blockedUser->getId(),
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'error' => 'Wystąpił błąd podczas odblokowywania użytkownika'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/blocked', name: 'app_user_blocked_list', methods: ['GET'])]
    public function blockedList(): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        
        return $this->render('users/blocked.html.twig', [
            'blockedUsers' => $currentUser->getBlockedUsers()
        ]);
    }
}
