<?php

namespace App\Repository;

use App\Entity\Competence;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Competence>
 *
 * @method Competence|null find($id, $lockMode = null, $lockVersion = null)
 * @method Competence|null findOneBy(array $criteria, array $orderBy = null)
 * @method Competence[]    findAll()
 * @method Competence[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CompetenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Competence::class);
    }

    public function save(Competence $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Competence $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find competences by IDModule
     *
     * @param string $idmodule
     * @return Competence[]
     */
    public function findByIdModule(string $idmodule): array
    {
        try {
            return $this->createQueryBuilder('c')
                ->andWhere('c.idmodule = :idmodule')
                ->setParameter('idmodule', $idmodule)
                ->orderBy('c.id', 'ASC')
                ->getQuery()
                ->getResult();
        } catch (\Exception $e) {
            error_log("CompetenceRepository::findByIdModule - Exception: " . $e->getMessage());
            // Fallback to direct findBy method
            return $this->findBy(['idmodule' => $idmodule]);
        }
    }
}
