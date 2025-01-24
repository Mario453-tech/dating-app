<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Pobiera konwersację między dwoma użytkownikami
     */
    public function getConversation(User $user1, User $user2, int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('m')
            ->where('(m.sender = :user1 AND m.receiver = :user2 AND m.isDeletedBySender = false) OR (m.sender = :user2 AND m.receiver = :user1 AND m.isDeletedByReceiver = false)')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Pobiera listę konwersacji użytkownika
     */
    public function getUserConversations(User $user): array
    {
        $qb = $this->createQueryBuilder('m')
            ->where('(m.sender = :user AND m.isDeletedBySender = false) OR (m.receiver = :user AND m.isDeletedByReceiver = false)')
            ->setParameter('user', $user)
            ->orderBy('m.createdAt', 'DESC');

        $results = $qb->getQuery()->getResult();
        
        // Grupujemy wiadomości według rozmówcy
        $conversations = [];
        foreach ($results as $message) {
            $otherUser = $message->getSender()->getId() === $user->getId() 
                ? $message->getReceiver() 
                : $message->getSender();
                
            $conversationId = $otherUser->getId();
            
            if (!isset($conversations[$conversationId])) {
                $conversations[$conversationId] = [
                    'user' => $otherUser,
                    'lastMessage' => $message,
                    'unreadCount' => 0
                ];
            }
            
            // Zliczamy nieprzeczytane wiadomości
            if ($message->getReceiver()->getId() === $user->getId() 
                && $message->getStatus() !== Message::STATUS_READ) {
                $conversations[$conversationId]['unreadCount']++;
            }
        }
        
        return array_values($conversations);
    }

    /**
     * Pobiera liczbę nieprzeczytanych wiadomości dla użytkownika
     */
    public function getUnreadCount(User $user): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.receiver = :user')
            ->andWhere('m.status != :status')
            ->andWhere('m.isDeletedByReceiver = false')
            ->setParameter('user', $user)
            ->setParameter('status', Message::STATUS_READ)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Oznacza wszystkie wiadomości w konwersacji jako przeczytane
     */
    public function markConversationAsRead(User $receiver, User $sender): void
    {
        $this->createQueryBuilder('m')
            ->update()
            ->set('m.status', ':status')
            ->where('m.sender = :sender')
            ->andWhere('m.receiver = :receiver')
            ->andWhere('m.status != :status')
            ->setParameter('status', Message::STATUS_READ)
            ->setParameter('sender', $sender)
            ->setParameter('receiver', $receiver)
            ->getQuery()
            ->execute();
    }

    /**
     * Pobiera liczbę wiadomości w konwersacji
     */
    public function getConversationCount(User $user1, User $user2): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('(m.sender = :user1 AND m.receiver = :user2) OR (m.sender = :user2 AND m.receiver = :user1)')
            ->andWhere('m.isDeletedBySender = false')
            ->andWhere('m.isDeletedByReceiver = false')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findUserConversations(User $user): array
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.sender = :user OR m.receiver = :user')
            ->setParameter('user', $user)
            ->orderBy('m.createdAt', 'DESC');

        $messages = $qb->getQuery()->getResult();

        $conversations = [];
        $processedUsers = [];

        foreach ($messages as $message) {
            $otherUser = $message->getSender()->getId() === $user->getId()
                ? $message->getReceiver()
                : $message->getSender();

            if (!isset($processedUsers[$otherUser->getId()])) {
                $hasUnread = $this->createQueryBuilder('m')
                    ->select('COUNT(m.id)')
                    ->where('m.receiver = :user')
                    ->andWhere('m.sender = :otherUser')
                    ->andWhere('m.status != :status')
                    ->setParameter('user', $user)
                    ->setParameter('otherUser', $otherUser)
                    ->setParameter('status', Message::STATUS_READ)
                    ->getQuery()
                    ->getSingleScalarResult() > 0;

                $conversations[] = [
                    'otherUser' => $otherUser,
                    'lastMessage' => $message,
                    'hasUnread' => $hasUnread
                ];
                $processedUsers[$otherUser->getId()] = true;
            }
        }

        return $conversations;
    }
}
