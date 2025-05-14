<?php

namespace App\Repository;

use App\Entity\SousCompetence;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SousCompetence>
 *
 * @method SousCompetence|null find($id, $lockMode = null, $lockVersion = null)
 * @method SousCompetence|null findOneBy(array $criteria, array $orderBy = null)
 * @method SousCompetence[]    findAll()
 * @method SousCompetence[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SousCompetenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SousCompetence::class);
    }

    public function save(SousCompetence $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SousCompetence $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
