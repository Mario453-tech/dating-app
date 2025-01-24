<?php

namespace App\Controller;

use App\Form\SearchType;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

class HomeController extends AbstractController
{
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager
    ) {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'app_home')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        
        if ($user) {
            $this->logger->info('Zalogowany użytkownik odwiedza stronę główną', [
                'user_id' => $user->getId()
            ]);
            
            // Pobierz sugerowanych użytkowników
            $suggestedUsers = $this->entityManager->getRepository(User::class)
                ->findSuggestedUsers($user);
                
            $this->logger->debug('Pobrano sugerowanych użytkowników', [
                'user_id' => $user->getId(),
                'suggested_count' => count($suggestedUsers)
            ]);
            
            $form = $this->createForm(SearchType::class);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                // TODO: Implement search logic
                $data = $form->getData();
            }

            // Example featured profiles
            $featuredProfiles = [
                [
                    'id' => 1,
                    'name' => 'Anna',
                    'age' => 28,
                    'location' => 'Warszawa',
                    'photo' => 'profile1.jpg'
                ],
                [
                    'id' => 2,
                    'name' => 'Piotr',
                    'age' => 32,
                    'location' => 'Kraków',
                    'photo' => 'profile2.jpg'
                ],
                // Add more profiles as needed
            ];

            return $this->render('home/index.html.twig', [
                'search_form' => $form->createView(),
                'featured_profiles' => $featuredProfiles,
                'suggestedUsers' => $suggestedUsers
            ]);
        }

        $this->logger->info('Niezalogowany użytkownik odwiedza stronę główną');
        return $this->render('home/welcome.html.twig');
    }

    #[Route('/search', name: 'app_search', methods: ['GET'])]
    public function search(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        /** @var User $user */
        $user = $this->getUser();
        $query = $request->query->get('q');
        
        $this->logger->info('Wyszukiwanie użytkowników', [
            'user_id' => $user->getId(),
            'query' => $query
        ]);

        try {
            $results = $this->entityManager->getRepository(User::class)
                ->searchUsers($query, $user);

            $this->logger->debug('Wyniki wyszukiwania', [
                'user_id' => $user->getId(),
                'query' => $query,
                'results_count' => count($results)
            ]);

            return $this->render('home/search.html.twig', [
                'results' => $results,
                'query' => $query
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Błąd podczas wyszukiwania', [
                'user_id' => $user->getId(),
                'query' => $query,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->addFlash('error', 'Wystąpił błąd podczas wyszukiwania.');
            return $this->redirectToRoute('app_home');
        }
    }
}
