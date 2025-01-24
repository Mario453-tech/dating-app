<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Interest;
use App\Form\InterestsType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/interests')]
class InterestsController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    #[Route('/', name: 'app_interests_index', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        /** @var User $user */
        $user = $this->getUser();
        
        $form = $this->createForm(InterestsType::class, $user, [
            'action' => $this->generateUrl('app_interests_save')
        ]);

        return $this->render('interests/index.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/save', name: 'app_interests_save', methods: ['POST'])]
    public function save(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        /** @var User $user */
        $user = $this->getUser();
        
        $form = $this->createForm(InterestsType::class, $user);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->entityManager->flush();
                
                $this->logger->info('Zainteresowania zostały zaktualizowane', [
                    'user_id' => $user->getId(),
                    'interests_count' => count($user->getInterests())
                ]);

                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => true,
                        'message' => 'Zainteresowania zostały zaktualizowane.'
                    ]);
                }

                $this->addFlash('success', 'Zainteresowania zostały zaktualizowane.');
                return $this->redirectToRoute('app_profile');
            } catch (\Exception $e) {
                $this->logger->error('Błąd podczas aktualizacji zainteresowań', [
                    'user_id' => $user->getId(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Wystąpił błąd podczas aktualizacji zainteresowań.'
                    ], 500);
                }

                $this->addFlash('error', 'Wystąpił błąd podczas aktualizacji zainteresowań.');
                return $this->redirectToRoute('app_interests_index');
            }
        }

        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        $this->logger->warning('Błędy walidacji formularza zainteresowań', [
            'user_id' => $user->getId(),
            'errors' => $errors
        ]);

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Formularz zawiera błędy.',
                'errors' => $errors
            ], 400);
        }

        return $this->render('interests/index.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
