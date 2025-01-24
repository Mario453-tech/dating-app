<?php

namespace App\Repository;

use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ResetPasswordRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResetPasswordRequest::class);
    }

    public function removeExpiredResetRequests(): int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'DELETE FROM reset_password_request WHERE expires_at < NOW()';
        return $conn->executeStatement($sql);
    }

    public function getUserMostRecentNonExpiredRequestDate(User $user): ?\DateTimeInterface
    {
        $qb = $this->createQueryBuilder('r');
        $qb->where('r.user = :user')
           ->andWhere('r.expiresAt > :now')
           ->setParameter('user', $user)
           ->setParameter('now', new \DateTime())
           ->orderBy('r.requestedAt', 'DESC')
           ->setMaxResults(1);

        $result = $qb->getQuery()->getOneOrNullResult();
        return $result ? $result->getRequestedAt() : null;
    }
}
