<?php

namespace App\Controller;

use App\Entity\Photo;
use App\Form\PhotoType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Form\FormInterface;

#[Route('/photo')]
#[IsGranted('ROLE_USER')]
class PhotoController extends AbstractController
{
    private string $photoDir;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private CsrfTokenManagerInterface $csrfTokenManager;
    private RequestStack $requestStack;

    public function __construct(
        EntityManagerInterface $entityManager, 
        LoggerInterface $logger,
        CsrfTokenManagerInterface $csrfTokenManager,
        RequestStack $requestStack
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->requestStack = $requestStack;
        $this->photoDir = __DIR__ . '/../../public/uploads/photos';
    }

    #[Route('/', name: 'photo_index', methods: ['GET'])]
    public function index(): Response
    {
        $photos = $this->entityManager->getRepository(Photo::class)->findAll();

        return $this->render('photo/index.html.twig', [
            'photos' => $photos,
        ]);
    }

    #[Route('/new', name: 'photo_new', methods: ['GET', 'POST'])]
    public function new(Request $request, SluggerInterface $slugger): Response
    {
        $this->logger->info('Rozpoczęcie procesu dodawania zdjęcia', [
            'method' => $request->getMethod(),
            'is_xhr' => $request->isXmlHttpRequest(),
            'user_authenticated' => $this->getUser() !== null
        ]);

        /** @var UserInterface $user */
        $user = $this->getUser();
        if (!$user) {
            $this->logger->warning('Próba dodania zdjęcia bez zalogowania');
            return $this->redirectToRoute('app_login');
        }
        
        $photo = new Photo();
        $photo->setUser($user);
        $form = $this->createForm(PhotoType::class, $photo);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->logger->info('Formularz został przesłany', [
                'is_valid' => $form->isValid(),
                'errors' => $this->getFormErrors($form)
            ]);

            if ($form->isValid()) {
                $photoFile = $form->get('photoFile')->getData();

                if ($photoFile) {
                    $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                    try {
                        $targetDirectory = $this->photoDir . '/' . $user->getId();
                        if (!file_exists($targetDirectory) && !mkdir($targetDirectory, 0777, true)) {
                            $this->logger->error('Nie można utworzyć katalogu na zdjęcia', [
                                'directory' => $targetDirectory
                            ]);
                            $this->addFlash('error', 'Nie można utworzyć katalogu na zdjęcia.');
                            return $this->redirectToRoute('photo_new');
                        }

                        $photoFile->move(
                            $targetDirectory,
                            $newFilename
                        );

                        $photo->setFilename($newFilename);
                        $this->entityManager->persist($photo);
                        $this->entityManager->flush();

                        $this->logger->info('Zdjęcie zostało dodane pomyślnie', [
                            'photo_id' => $photo->getId(),
                            'filename' => $newFilename
                        ]);

                        $this->addFlash('success', 'Zdjęcie zostało dodane pomyślnie.');
                        return $this->redirectToRoute('photo_index');

                    } catch (\Exception $e) {
                        $this->logger->error('Błąd podczas przesyłania zdjęcia', [
                            'error' => $e->getMessage(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine()
                        ]);

                        $this->addFlash('error', 'Wystąpił błąd podczas przesyłania zdjęcia.');
                        return $this->redirectToRoute('photo_new');
                    }
                }
            }
        }

        return $this->render('photo/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'photo_show', methods: ['GET'])]
    public function show(Photo $photo): Response
    {
        return $this->render('photo/show.html.twig', [
            'photo' => $photo,
        ]);
    }

    #[Route('/{id}/edit', name: 'photo_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Photo $photo, SluggerInterface $slugger): Response
    {
        // Sprawdź czy użytkownik jest właścicielem zdjęcia
        if ($photo->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Nie masz uprawnień do edycji tego zdjęcia.');
        }

        $form = $this->createForm(PhotoType::class, $photo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photoFile')->getData();

            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                try {
                    // Usuń stare zdjęcie
                    $oldFilename = $photo->getFilename();
                    if ($oldFilename) {
                        $oldFilePath = $this->photoDir . '/' . $oldFilename;
                        if (file_exists($oldFilePath)) {
                            unlink($oldFilePath);
                        }
                    }

                    $photoFile->move(
                        $this->photoDir,
                        $newFilename
                    );
                    $photo->setFilename($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Wystąpił błąd podczas aktualizacji zdjęcia.');
                    return $this->redirectToRoute('photo_edit', ['id' => $photo->getId()]);
                }
            }

            $this->entityManager->flush();
            $this->addFlash('success', 'Zdjęcie zostało zaktualizowane.');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('photo/edit.html.twig', [
            'photo' => $photo,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/set-as-profile', name: 'photo_set_as_profile', methods: ['POST'])]
    public function setAsProfile(Request $request, Photo $photo): Response
    {
        // Sprawdź czy użytkownik jest właścicielem zdjęcia
        if ($photo->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Nie masz uprawnień do ustawienia tego zdjęcia jako profilowe.');
        }

        if ($this->isCsrfTokenValid('set-as-profile'.$photo->getId(), $request->request->get('_token'))) {
            try {
                $user = $this->getUser();
                $filesystem = new Filesystem();
                
                // Skopiuj zdjęcie do katalogu ze zdjęciami profilowymi
                $sourceFile = $this->photoDir . '/' . $photo->getFilename();
                $profilePhotosDir = __DIR__ . '/../../public/uploads/profile_photos';
                
                // Upewnij się, że katalog istnieje
                if (!is_dir($profilePhotosDir)) {
                    $filesystem->mkdir($profilePhotosDir);
                }
                
                // Usuń stare zdjęcie profilowe jeśli istnieje
                if ($user->getProfilePhoto()) {
                    $oldProfilePhoto = $profilePhotosDir . '/' . $user->getProfilePhoto();
                    if ($filesystem->exists($oldProfilePhoto)) {
                        $filesystem->remove($oldProfilePhoto);
                    }
                }
                
                // Skopiuj nowe zdjęcie jako profilowe
                $newProfilePhotoName = 'profile-' . uniqid() . '.' . pathinfo($photo->getFilename(), PATHINFO_EXTENSION);
                $filesystem->copy($sourceFile, $profilePhotosDir . '/' . $newProfilePhotoName);
                
                // Zaktualizuj użytkownika
                $user->setProfilePhoto($newProfilePhotoName);
                $this->entityManager->flush();
                
                $this->addFlash('success', 'Zdjęcie profilowe zostało zaktualizowane.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Wystąpił błąd podczas ustawiania zdjęcia profilowego.');
            }
        }

        return $this->redirectToRoute('app_profile');
    }

    #[Route('/{id}', name: 'photo_delete', methods: ['POST'])]
    public function delete(Request $request, Photo $photo): Response
    {
        // Sprawdź czy użytkownik jest właścicielem zdjęcia
        if ($photo->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Nie masz uprawnień do usunięcia tego zdjęcia.');
        }

        if ($this->isCsrfTokenValid('delete'.$photo->getId(), $request->request->get('_token'))) {
            try {
                // Usuń plik zdjęcia
                $filename = $photo->getFilename();
                if ($filename) {
                    $filePath = $this->photoDir . '/' . $filename;
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }

                $this->entityManager->remove($photo);
                $this->entityManager->flush();
                
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => true,
                        'message' => 'Zdjęcie zostało usunięte.'
                    ]);
                }
                
                $this->addFlash('success', 'Zdjęcie zostało usunięte.');
            } catch (\Exception $e) {
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Wystąpił błąd podczas usuwania zdjęcia.'
                    ], 500);
                }
                
                $this->addFlash('error', 'Wystąpił błąd podczas usuwania zdjęcia.');
            }
        }

        return $this->redirectToRoute('app_profile');
    }

    private function getFormErrors(FormInterface $form)
    {
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }
        return $errors;
    }
}
