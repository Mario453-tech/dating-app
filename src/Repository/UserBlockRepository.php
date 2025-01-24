<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserBlock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserBlock>
 *
 * @method UserBlock|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserBlock|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserBlock[]    findAll()
 * @method UserBlock[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserBlockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserBlock::class);
    }

    public function isUserBlocked(User $blocker, User $blocked): bool
    {
        return null !== $this->findOneBy([
            'blocker' => $blocker,
            'blocked' => $blocked
        ]);
    }

    public function getBlockedUsers(User $blocker): array
    {
        return $this->createQueryBuilder('ub')
            ->select('IDENTITY(ub.blocked) as userId')
            ->where('ub.blocker = :blocker')
            ->setParameter('blocker', $blocker)
            ->getQuery()
            ->getResult();
    }
}
