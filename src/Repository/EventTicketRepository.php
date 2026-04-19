<?php

namespace App\Repository;

use App\Entity\EventTicket;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EventTicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventTicket::class);
    }

    /**
     * @return EventTicket[]
     */
    public function findTicketsForReminder(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        return $this->createQueryBuilder('ticket')
            ->innerJoin('ticket.event', 'event')
            ->addSelect('event')
            ->andWhere('ticket.reminderSentAt IS NULL')
            ->andWhere('event.eventDate BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('event.eventDate', 'ASC')
            ->addOrderBy('ticket.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
