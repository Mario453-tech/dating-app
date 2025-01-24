<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Interest;
use App\Entity\InterestCategory;
use App\Form\InterestsType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/interests')]
class InterestController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'app_interests')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        /** @var User $user */
        $user = $this->getUser();
        
        // Pobierz wszystkie kategorie
        $categories = $this->entityManager->getRepository(InterestCategory::class)->findAll();
        
        // Pobierz zainteresowania pogrupowane według kategorii
        $interestsByCategory = [];
        foreach ($categories as $category) {
            $interests = $this->entityManager->getRepository(Interest::class)
                ->createQueryBuilder('i')
                ->where('i.category = :category')
                ->setParameter('category', $category)
                ->getQuery()
                ->getResult();
                
            if (count($interests) > 0) {
                $interestsByCategory[$category->getId()] = $interests;
            }
        }

        // Pobierz ID zainteresowań użytkownika
        $userInterestIds = array_map(function($interest) {
            return $interest->getId();
        }, $user->getInterests()->toArray());

        return $this->render('interests/index.html.twig', [
            'categories' => $categories,
            'interestsByCategory' => $interestsByCategory,
            'userInterestIds' => $userInterestIds
        ]);
    }

    #[Route('/add', name: 'app_interest_add', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $name = $request->request->get('name');
        $categoryId = $request->request->get('category');

        if (!$name || !$categoryId) {
            return $this->json([
                'success' => false,
                'message' => 'Nazwa i kategoria są wymagane.'
            ]);
        }

        $category = $this->entityManager->getRepository(InterestCategory::class)->find($categoryId);
        if (!$category) {
            return $this->json([
                'success' => false,
                'message' => 'Wybrana kategoria nie istnieje.'
            ]);
        }

        // Sprawdź czy zainteresowanie już istnieje
        $existingInterest = $this->entityManager->getRepository(Interest::class)
            ->findOneBy(['name' => $name]);

        if ($existingInterest) {
            return $this->json([
                'success' => false,
                'message' => 'Takie zainteresowanie już istnieje.'
            ]);
        }

        // Utwórz nowe zainteresowanie
        $interest = new Interest();
        $interest->setName($name);
        $interest->setCategory($category);

        $this->entityManager->persist($interest);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'interest' => [
                'id' => $interest->getId(),
                'name' => $interest->getName(),
                'categoryId' => $category->getId()
            ],
            'message' => 'Zainteresowanie zostało dodane.'
        ]);
    }

    #[Route('/edit/{id}', name: 'app_interest_edit', methods: ['POST'])]
    public function edit(Interest $interest, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $name = $request->request->get('name');
        $categoryId = $request->request->get('category');

        if (!$name || !$categoryId) {
            return $this->json([
                'success' => false,
                'message' => 'Nazwa i kategoria są wymagane.'
            ]);
        }

        $category = $this->entityManager->getRepository(InterestCategory::class)->find($categoryId);
        if (!$category) {
            return $this->json([
                'success' => false,
                'message' => 'Wybrana kategoria nie istnieje.'
            ]);
        }

        // Sprawdź czy zainteresowanie o takiej nazwie już istnieje
        $existingInterest = $this->entityManager->getRepository(Interest::class)
            ->findOneBy(['name' => $name]);

        if ($existingInterest && $existingInterest->getId() !== $interest->getId()) {
            return $this->json([
                'success' => false,
                'message' => 'Takie zainteresowanie już istnieje.'
            ]);
        }

        $interest->setName($name);
        $interest->setCategory($category);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'interest' => [
                'id' => $interest->getId(),
                'name' => $interest->getName(),
                'categoryId' => $category->getId()
            ],
            'message' => 'Zainteresowanie zostało zaktualizowane.'
        ]);
    }

    #[Route('/delete/{id}', name: 'app_interest_delete', methods: ['POST'])]
    public function delete(Interest $interest): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // Sprawdź czy zainteresowanie jest używane
        $users = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where(':interest MEMBER OF u.interests')
            ->setParameter('interest', $interest)
            ->getQuery()
            ->getResult();

        if (count($users) > 0) {
            return $this->json([
                'success' => false,
                'message' => 'Nie można usunąć zainteresowania, które jest używane przez użytkowników.'
            ]);
        }

        $this->entityManager->remove($interest);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Zainteresowanie zostało usunięte.'
        ]);
    }

    #[Route('/toggle/{id}', name: 'app_interest_toggle', methods: ['POST'])]
    public function toggle(Interest $interest): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        if ($user->getInterests()->contains($interest)) {
            $user->removeInterest($interest);
        } else {
            $user->addInterest($interest);
        }

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Zainteresowanie zostało zaktualizowane.'
        ]);
    }
}
