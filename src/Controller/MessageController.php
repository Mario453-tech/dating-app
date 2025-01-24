<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\RateLimiter\RateLimiterFactory;

#[Route('/messages')]
#[IsGranted('ROLE_USER')]
class MessageController extends AbstractController
{
    private const MAX_MESSAGES_PER_HOUR = 100;
    private const MAX_MESSAGES_PER_CONVERSATION = 1000;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageRepository $messageRepository,
        private UserRepository $userRepository,
        #[Autowire(service: 'monolog.logger.messages')]
        private LoggerInterface $logger,
        #[Autowire(service: 'limiter.message')]
        private RateLimiterFactory $messageLimiter,
        #[Autowire('%app.message_encryption_key%')]
        private string $encryptionKey
    ) {
    }

    private function validateMessagePermissions(User $sender, User $receiver): array
    {
        // Sprawdź czy użytkownicy istnieją i są aktywni
        if (!$sender->isEnabled() || !$receiver->isEnabled()) {
            return [
                'isValid' => false,
                'error' => 'Konto nadawcy lub odbiorcy jest nieaktywne',
                'code' => Response::HTTP_FORBIDDEN
            ];
        }

        // Sprawdź czy użytkownik nie jest zbanowany
        if ($sender->isBanned() || $receiver->isBanned()) {
            return [
                'isValid' => false,
                'error' => 'Konto nadawcy lub odbiorcy jest zablokowane',
                'code' => Response::HTTP_FORBIDDEN
            ];
        }

        // Sprawdź czy odbiorca nie zablokował nadawcy
        if ($receiver->hasBlockedUser($sender)) {
            return [
                'isValid' => false,
                'error' => 'Nie możesz wysłać wiadomości do tego użytkownika',
                'code' => Response::HTTP_FORBIDDEN
            ];
        }

        // Sprawdź limit wiadomości w konwersacji
        $conversationCount = $this->messageRepository->getConversationCount($sender, $receiver);
        if ($conversationCount >= self::MAX_MESSAGES_PER_CONVERSATION) {
            return [
                'isValid' => false,
                'error' => 'Osiągnięto limit wiadomości w tej konwersacji',
                'code' => Response::HTTP_TOO_MANY_REQUESTS
            ];
        }

        return ['isValid' => true];
    }

    private function encryptMessage(string $content): array
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $encryptedContent = sodium_crypto_secretbox(
            $content,
            $nonce,
            sodium_hex2bin($this->encryptionKey)
        );
        
        return [
            'content' => base64_encode($encryptedContent),
            'nonce' => base64_encode($nonce)
        ];
    }

    private function decryptMessage(string $encryptedContent, ?string $nonce): string
    {
        try {
            // Jeśli nie ma nonce, zwróć wiadomość jako zwykły tekst
            if ($nonce === null) {
                return $encryptedContent;
            }

            $decrypted = sodium_crypto_secretbox_open(
                base64_decode($encryptedContent),
                base64_decode($nonce),
                sodium_hex2bin($this->encryptionKey)
            );

            if ($decrypted === false) {
                throw new \RuntimeException('Nie można odszyfrować wiadomości');
            }

            return $decrypted;
        } catch (\Exception $e) {
            $this->logger->error('Błąd odszyfrowywania wiadomości', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return '[Nie można odszyfrować wiadomości]';
        }
    }

    #[Route('/', name: 'app_messages')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $conversations = $this->messageRepository->findUserConversations($user);
        
        return $this->render('message/index.html.twig', [
            'conversations' => $conversations
        ]);
    }

    #[Route('/', name: 'app_messages_index', methods: ['GET'])]
    public function index2(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user->isEnabled()) {
            $this->addFlash('error', 'Twoje konto jest nieaktywne');
            return $this->redirectToRoute('app_home');
        }

        $conversations = $this->messageRepository->getUserConversations($user);

        $this->logger->info('Użytkownik wyświetlił listę konwersacji', [
            'user_id' => $user->getId()
        ]);

        return $this->render('messages/index.html.twig', [
            'conversations' => $conversations
        ]);
    }

    #[Route('/conversation/{id}', name: 'app_messages_conversation', methods: ['GET'])]
    public function conversation(User $otherUser): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Sprawdź uprawnienia
        $validation = $this->validateMessagePermissions($currentUser, $otherUser);
        if (!$validation['isValid']) {
            $this->addFlash('error', $validation['error']);
            return $this->redirectToRoute('app_messages_index');
        }
        
        // Oznacz wiadomości jako przeczytane
        $this->messageRepository->markConversationAsRead($currentUser, $otherUser);
        
        $messages = $this->messageRepository->getConversation($currentUser, $otherUser);

        // Odszyfruj wiadomości
        foreach ($messages as $message) {
            try {
                $message->setContent(
                    $this->decryptMessage($message->getContent(), $message->getEncryptionNonce())
                );
            } catch (\RuntimeException $e) {
                $this->logger->error('Błąd odszyfrowywania wiadomości', [
                    'message_id' => $message->getId(),
                    'error' => $e->getMessage()
                ]);
                $message->setContent('[Nie można odszyfrować wiadomości]');
            }
        }

        $this->logger->info('Użytkownik wyświetlił konwersację', [
            'user_id' => $currentUser->getId(),
            'other_user_id' => $otherUser->getId(),
            'messages_count' => count($messages)
        ]);

        return $this->render('messages/conversation.html.twig', [
            'messages' => $messages,
            'otherUser' => $otherUser
        ]);
    }

    #[Route('/send/{id}', name: 'app_messages_send', methods: ['POST'])]
    public function send(Request $request, User $receiver): JsonResponse
    {
        /** @var User $sender */
        $sender = $this->getUser();

        // Sprawdź uprawnienia
        $validation = $this->validateMessagePermissions($sender, $receiver);
        if (!$validation['isValid']) {
            return $this->json(['error' => $validation['error']], $validation['code']);
        }

        $content = $request->request->get('content');
        if (empty($content)) {
            $this->logger->warning('Próba wysłania pustej wiadomości', [
                'sender_id' => $sender->getId(),
                'receiver_id' => $receiver->getId()
            ]);
            return $this->json(['error' => 'Treść wiadomości nie może być pusta'], Response::HTTP_BAD_REQUEST);
        }

        // Sprawdź limit wiadomości na godzinę
        $limiter = $this->messageLimiter->create($sender->getId());
        if (false === $limiter->consume()->isAccepted()) {
            $this->logger->warning('Przekroczono limit wiadomości', [
                'sender_id' => $sender->getId(),
                'receiver_id' => $receiver->getId()
            ]);
            return $this->json([
                'error' => 'Przekroczono limit wiadomości. Spróbuj ponownie później.'
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        // Zaszyfruj i wyślij wiadomość
        try {
            $encrypted = $this->encryptMessage($content);
            
            $message = new Message();
            $message->setSender($sender)
                ->setReceiver($receiver)
                ->setContent($encrypted['content'])
                ->setEncryptionNonce($encrypted['nonce']);

            $this->entityManager->persist($message);
            $this->entityManager->flush();

            $this->logger->info('Wysłano nową wiadomość', [
                'message_id' => $message->getId(),
                'sender_id' => $sender->getId(),
                'receiver_id' => $receiver->getId()
            ]);

            return $this->json([
                'status' => 'success',
                'message' => 'Wiadomość została wysłana'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Błąd podczas szyfrowania/wysyłania wiadomości', [
                'sender_id' => $sender->getId(),
                'receiver_id' => $receiver->getId(),
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'error' => 'Wystąpił błąd podczas wysyłania wiadomości'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/delete/{id}', name: 'app_messages_delete', methods: ['POST'])]
    public function delete(Message $message): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Sprawdź, czy użytkownik ma prawo usunąć wiadomość
        if ($message->getSender() !== $currentUser && $message->getReceiver() !== $currentUser) {
            $this->logger->warning('Próba nieuprawnionego usunięcia wiadomości', [
                'user_id' => $currentUser->getId(),
                'message_id' => $message->getId(),
                'message_sender_id' => $message->getSender()->getId(),
                'message_receiver_id' => $message->getReceiver()->getId()
            ]);
            return $this->json(['error' => 'Brak dostępu'], Response::HTTP_FORBIDDEN);
        }

        // Oznacz wiadomość jako usuniętą dla odpowiedniego użytkownika
        if ($message->getSender() === $currentUser) {
            $message->setIsDeletedBySender(true);
        } else {
            $message->setIsDeletedByReceiver(true);
        }

        // Jeśli wiadomość została usunięta przez obu użytkowników, usuń ją z bazy
        if ($message->isDeletedBySender() && $message->isDeletedByReceiver()) {
            $this->entityManager->remove($message);
            $this->logger->info('Wiadomość została całkowicie usunięta', [
                'message_id' => $message->getId()
            ]);
        } else {
            $this->logger->info('Wiadomość została oznaczona jako usunięta', [
                'message_id' => $message->getId(),
                'deleted_by_sender' => $message->isDeletedBySender(),
                'deleted_by_receiver' => $message->isDeletedByReceiver()
            ]);
        }

        $this->entityManager->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/unread-count', name: 'app_messages_unread_count', methods: ['GET'])]
    public function unreadCount(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $count = $this->messageRepository->getUnreadCount($user);

        $this->logger->debug('Pobrano liczbę nieprzeczytanych wiadomości', [
            'user_id' => $user->getId(),
            'unread_count' => $count
        ]);

        return $this->json(['count' => $count]);
    }

    #[Route('/api', name: 'api_messages_list', methods: ['GET'])]
    public function apiList(Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        
        $userId = $request->query->get('userId');
        $otherUser = $userId ? $this->userRepository->find($userId) : null;
        
        if ($userId && !$otherUser) {
            return $this->json(['error' => 'Użytkownik nie istnieje'], Response::HTTP_NOT_FOUND);
        }
        
        if ($otherUser) {
            $messages = $this->messageRepository->getConversation($currentUser, $otherUser);
        } else {
            $conversations = $this->messageRepository->getUserConversations($currentUser);
            return $this->json([
                'conversations' => array_map(function($conv) {
                    return [
                        'user' => [
                            'id' => $conv['user']->getId(),
                            'username' => $conv['user']->getUsername()
                        ],
                        'lastMessage' => [
                            'content' => $conv['lastMessage']->getContent(),
                            'createdAt' => $conv['lastMessage']->getCreatedAt()->format('c'),
                            'status' => $conv['lastMessage']->getStatus()
                        ],
                        'unreadCount' => $conv['unreadCount']
                    ];
                }, $conversations)
            ]);
        }

        return $this->json([
            'messages' => array_map(function(Message $message) {
                return [
                    'id' => $message->getId(),
                    'content' => $message->getContent(),
                    'sender' => [
                        'id' => $message->getSender()->getId(),
                        'username' => $message->getSender()->getUsername()
                    ],
                    'receiver' => [
                        'id' => $message->getReceiver()->getId(),
                        'username' => $message->getReceiver()->getUsername()
                    ],
                    'createdAt' => $message->getCreatedAt()->format('c'),
                    'status' => $message->getStatus()
                ];
            }, $messages)
        ]);
    }

    #[Route('/api', name: 'api_messages_send', methods: ['POST'])]
    public function apiSend(Request $request): JsonResponse
    {
        /** @var User $sender */
        $sender = $this->getUser();
        
        // Sprawdź limit wiadomości
        $limiter = $this->messageLimiter->create($sender->getId());
        if (false === $limiter->consume()->isAccepted()) {
            $this->logger->warning('Przekroczono limit wysyłania wiadomości', [
                'user_id' => $sender->getId()
            ]);
            return $this->json([
                'error' => 'Przekroczono limit wysyłania wiadomości. Spróbuj ponownie później.'
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['receiverId']) || !isset($data['content'])) {
            return $this->json(['error' => 'Brak wymaganych pól'], Response::HTTP_BAD_REQUEST);
        }

        $receiver = $this->userRepository->find($data['receiverId']);
        if (!$receiver) {
            return $this->json(['error' => 'Odbiorca nie istnieje'], Response::HTTP_NOT_FOUND);
        }

        // Sprawdź czy użytkownik może wysłać wiadomość do odbiorcy
        if (!$this->canSendMessage($sender, $receiver)) {
            return $this->json(['error' => 'Nie możesz wysłać wiadomości do tego użytkownika'], Response::HTTP_FORBIDDEN);
        }

        // Zaszyfruj wiadomość
        try {
            $encrypted = $this->encryptMessage($data['content']);
            
            $message = new Message();
            $message->setSender($sender)
                ->setReceiver($receiver)
                ->setContent($encrypted['content'])
                ->setEncryptionNonce($encrypted['nonce']);

            $this->entityManager->persist($message);
            $this->entityManager->flush();

            $this->logger->info('Wysłano nową wiadomość przez API', [
                'message_id' => $message->getId(),
                'sender_id' => $sender->getId(),
                'receiver_id' => $receiver->getId()
            ]);

            return $this->json([
                'id' => $message->getId(),
                'content' => $message->getContent(),
                'createdAt' => $message->getCreatedAt()->format('c'),
                'status' => $message->getStatus()
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $this->logger->error('Błąd podczas szyfrowania/wysyłania wiadomości', [
                'sender_id' => $sender->getId(),
                'receiver_id' => $receiver->getId(),
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'error' => 'Wystąpił błąd podczas wysyłania wiadomości'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/{id}', name: 'api_messages_update_status', methods: ['PATCH'])]
    public function apiUpdateStatus(Message $message, Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Sprawdź czy użytkownik ma prawo zmienić status wiadomości
        if ($message->getReceiver() !== $currentUser) {
            $this->logger->warning('Próba nieuprawnionej zmiany statusu wiadomości', [
                'user_id' => $currentUser->getId(),
                'message_id' => $message->getId()
            ]);
            return $this->json(['error' => 'Brak dostępu'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['status']) || $data['status'] !== Message::STATUS_READ) {
            return $this->json(['error' => 'Nieprawidłowy status'], Response::HTTP_BAD_REQUEST);
        }

        $message->markAsRead();
        $this->entityManager->flush();

        $this->logger->info('Zaktualizowano status wiadomości', [
            'message_id' => $message->getId(),
            'new_status' => $message->getStatus()
        ]);

        return $this->json([
            'id' => $message->getId(),
            'status' => $message->getStatus()
        ]);
    }

    private function canSendMessage(User $sender, User $receiver): bool
    {
        // Tutaj możesz dodać różne warunki, np.:
        // - Czy użytkownik nie jest zablokowany
        // - Czy użytkownicy są w odpowiednim wieku
        // - Czy profil jest kompletny
        // - Czy użytkownik ma odpowiedni status konta
        
        if ($sender->isBanned() || !$sender->isActive()) {
            return false;
        }

        // Sprawdź czy odbiorca nie zablokował nadawcy
        // To wymaga dodatkowej implementacji systemu blokowania
        
        return true;
    }
}
