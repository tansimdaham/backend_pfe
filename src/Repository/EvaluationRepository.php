<?php

namespace App\Repository;

use App\Entity\Evaluation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Evaluation>
 */
class EvaluationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evaluation::class);
    }

   /**
     * Trouve les évaluations par idmodule
     * @param string $idmodule - L'identifiant du module
     * @return Evaluation[] Returns an array of Evaluation objects
     */
    public function findByIdmodule(string $idmodule): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.idmodule = :idmodule')
            ->setParameter('idmodule', $idmodule)
            ->orderBy('e.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Trouve une évaluation par idmodule et apprenant
     * @param string $idmodule - L'identifiant du module
     * @param int $apprenantId - L'ID de l'apprenant
     * @return Evaluation|null
     */
    public function findOneByIdmoduleAndApprenant(string $idmodule, int $apprenantId): ?Evaluation
    {
        // Maintenant nous pouvons utiliser directement la relation avec l'apprenant
        return $this->createQueryBuilder('e')
            ->andWhere('e.idmodule = :idmodule')
            ->andWhere('e.apprenant = :apprenantId')
            ->setParameter('idmodule', $idmodule)
            ->setParameter('apprenantId', $apprenantId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
