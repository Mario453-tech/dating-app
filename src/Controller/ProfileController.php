<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Photo;
use App\Entity\Interest;
use App\Form\ProfileType;
use App\Service\GravatarService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Doctrine\Common\Collections\Collection;

#[Route('/profile')]
class ProfileController extends AbstractController
{
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private GravatarService $gravatarService;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        GravatarService $gravatarService
    ) {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->gravatarService = $gravatarService;
    }

    #[Route('/', name: 'app_profile')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        /** @var User $user */
        $user = $this->getUser();
        
        $gravatarImage = $this->gravatarService->getProfileImage($user->getEmail(), 300);
        
        $this->logger->info('Wyświetlanie profilu użytkownika', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'profile_photo' => $user->getProfilePhoto(),
            'gravatar_image' => $gravatarImage
        ]);

        $photos = $this->entityManager->getRepository(Photo::class)->findBy(['user' => $user]);

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'photos' => $photos,
            'gravatar_image' => $gravatarImage
        ]);
    }

    #[Route('/{id}', name: 'app_profile_show', requirements: ['id' => '\d+'])]
    public function show(User $user): Response
    {
        $gravatarImage = $this->gravatarService->getProfileImage($user->getEmail(), 300);

        return $this->render('profile/show.html.twig', [
            'user' => $user,
            'gravatarImage' => $gravatarImage
        ]);
    }

    #[Route('/{id}', name: 'app_profile_view', requirements: ['id' => '\d+'])]
    public function view(User $user): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        
        // Sprawdź czy użytkownik nie jest zbanowany
        if ($user->isBanned()) {
            throw $this->createAccessDeniedException('Ten profil jest niedostępny.');
        }
        
        // Sprawdź czy użytkownik nie jest zablokowany
        if ($currentUser->hasBlockedUser($user) || $user->hasBlockedUser($currentUser)) {
            throw $this->createAccessDeniedException('Ten profil jest niedostępny.');
        }
        
        $gravatarImage = $this->gravatarService->getProfileImage($user->getEmail(), 300);
        
        return $this->render('profile/view.html.twig', [
            'user' => $user,
            'gravatar_image' => $gravatarImage
        ]);
    }

    private function getFieldValue($value): string
    {
        if ($value === null) {
            return '';
        }
        
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        
        if ($value instanceof Interest) {
            return $value->getName();
        }
        
        if (is_array($value) || $value instanceof Collection) {
            $values = [];
            foreach ($value as $item) {
                if ($item instanceof Interest) {
                    $values[] = $item->getName();
                } else {
                    $values[] = (string)$item;
                }
            }
            return implode(',', $values);
        }
        
        return (string)$value;
    }

    private function compareData(array $old, array $new): array
    {
        $changedFields = [];
        foreach ($old as $field => $value) {
            $oldValue = $this->getFieldValue($value);
            $newValue = $this->getFieldValue($new[$field]);
            
            if ($oldValue !== $newValue) {
                $changedFields[] = $field;
            }
        }
        return $changedFields;
    }

    #[Route('/edit', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        /** @var User $user */
        $user = $this->getUser();
        $originalData = [
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'birthDate' => $user->getBirthDate(),
            'gender' => $user->getGender(),
            'seekingGender' => $user->getSeekingGender(),
            'location' => $user->getLocation(),
            'bio' => $user->getBio(),
            'interests' => $user->getInterests()
        ];
        
        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Porównujemy stare i nowe dane, aby znaleźć zmienione pola
                $newData = [
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'birthDate' => $user->getBirthDate(),
                    'gender' => $user->getGender(),
                    'seekingGender' => $user->getSeekingGender(),
                    'location' => $user->getLocation(),
                    'bio' => $user->getBio(),
                    'interests' => $user->getInterests()
                ];
                
                $changedFields = $this->compareData($originalData, $newData);
                
                $this->entityManager->flush();
                
                $this->logger->info('Profil użytkownika został zaktualizowany', [
                    'user_id' => $user->getId(),
                    'updated_fields' => $changedFields
                ]);

                $this->addFlash('success', 'Profil został zaktualizowany.');
                return $this->redirectToRoute('app_profile');
            } catch (\Exception $e) {
                $this->logger->error('Błąd podczas aktualizacji profilu', [
                    'user_id' => $user->getId(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                $this->addFlash('error', 'Wystąpił błąd podczas aktualizacji profilu.');
            }
        } elseif ($form->isSubmitted()) {
            $this->logger->warning('Błędy walidacji formularza profilu', [
                'user_id' => $user->getId(),
                'errors' => $form->getErrors(true, false)
            ]);
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
