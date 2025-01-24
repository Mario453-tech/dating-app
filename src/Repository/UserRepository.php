<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findByEmailOrUsername(string $identifier): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.email = :identifier')
            ->orWhere('u.username = :identifier')
            ->setParameter('identifier', $identifier)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findSuggestedUsers(User $user, int $limit = 10): array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = sprintf('
            SELECT u.* 
            FROM user u
            WHERE u.id != :userId
            AND u.is_active = :isActive
            AND u.is_banned = :isBanned
            AND (u.gender = :seekingGender OR :seekingGender = :allGender)
            AND (u.seeking_gender = :userGender OR u.seeking_gender = :allGender)
            ORDER BY RAND()
            LIMIT %d
        ', $limit);
        
        $stmt = $conn->prepare($sql);
        $stmt->bindValue('userId', $user->getId());
        $stmt->bindValue('isActive', true);
        $stmt->bindValue('isBanned', false);
        $stmt->bindValue('seekingGender', $user->getSeekingGender());
        $stmt->bindValue('userGender', $user->getGender());
        $stmt->bindValue('allGender', 'A');
        
        $resultSet = $stmt->executeQuery()->fetchAllAssociative();
        
        return array_map(function ($userData) {
            return $this->getEntityManager()->getRepository(User::class)->find($userData['id']);
        }, $resultSet);
    }

    public function searchUsers(string $query, User $currentUser): array
    {
        $qb = $this->createQueryBuilder('u');
        
        return $qb
            ->where('u.id != :currentUserId')
            ->andWhere('u.isActive = :isActive')
            ->andWhere('u.isBanned = :isBanned')
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('u.username', ':query'),
                    $qb->expr()->like('u.firstName', ':query'),
                    $qb->expr()->like('u.lastName', ':query'),
                    $qb->expr()->like('u.location', ':query')
                )
            )
            ->setParameter('currentUserId', $currentUser->getId())
            ->setParameter('isActive', true)
            ->setParameter('isBanned', false)
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('u.username', 'ASC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();
    }

    public function findMatches(User $user): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.id != :userId')
            ->andWhere('u.isActive = true')
            ->andWhere('u.isBanned = false')
            ->andWhere('u.roles NOT LIKE :adminRole')
            ->setParameter('userId', $user->getId())
            ->setParameter('adminRole', '%ROLE_ADMIN%')
            ->orderBy('u.updatedAt', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();
    }
}
