<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * @return Event[]
     */
    public function findFiltered(?string $dateFilter = null, ?string $location = null, ?string $categoryId = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.category', 'c')
            ->addSelect('c')
            ->orderBy('e.eventDate', 'ASC');

        if ($location) {
            $qb->andWhere('LOWER(e.location) = :location')
                ->setParameter('location', mb_strtolower($location));
        }

        if ($categoryId) {
            $qb->andWhere('c.id = :categoryId')
                ->setParameter('categoryId', (int) $categoryId);
        }

        if ($dateFilter === 'upcoming') {
            $qb->andWhere('e.eventDate >= :now')
                ->setParameter('now', new \DateTimeImmutable());
        }

        if ($dateFilter === 'past') {
            $qb->andWhere('e.eventDate < :now')
                ->setParameter('now', new \DateTimeImmutable());
        }

        return $qb->getQuery()->getResult();
    }
}
