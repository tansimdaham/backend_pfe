<?php

namespace App\Repository;

use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Evenement>
 */
class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    /**
     * Trouve les événements associés à un administrateur
     */
    public function findByAdministrateur(int $administrateurId): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.administrateurs', 'a')
            ->andWhere('a.id = :adminId')
            ->setParameter('adminId', $administrateurId)
            ->orderBy('e.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les événements dans une plage de dates
     */
    public function findByDateRange(\DateTime $start, \DateTime $end): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.dateDebut >= :start')
            ->andWhere('e.dateDebut <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('e.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les prochains événements
     * Inclut les événements du jour actuel et les événements futurs
     */
    public function findUpcomingEvents(int $limit = 5): array
    {
        $now = new \DateTime();
        $today = new \DateTime($now->format('Y-m-d') . ' 00:00:00');

        return $this->createQueryBuilder('e')
            ->where('e.dateDebut >= :today OR (e.dateDebut <= :now AND e.dateFin >= :now)')
            ->setParameter('today', $today)
            ->setParameter('now', $now)
            ->orderBy('e.dateDebut', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
