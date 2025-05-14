<?php

namespace App\Repository;

use App\Entity\Action;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Action>
 *
 * @method Action|null find($id, $lockMode = null, $lockVersion = null)
 * @method Action|null findOneBy(array $criteria, array $orderBy = null)
 * @method Action[]    findAll()
 * @method Action[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Action::class);
    }

    public function save(Action $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Action $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find actions by IDModule
     *
     * @param string $idmodule
     * @return Action[]
     */
    public function findByIdModule(string $idmodule): array
    {
        try {
            return $this->createQueryBuilder('a')
                ->andWhere('a.idmodule = :idmodule')
                ->setParameter('idmodule', $idmodule)
                ->orderBy('a.id', 'ASC')
                ->getQuery()
                ->getResult();
        } catch (\Exception $e) {
            error_log("ActionRepository::findByIdModule - Exception: " . $e->getMessage());
            // Fallback to direct findBy method
            return $this->findBy(['idmodule' => $idmodule]);
        }
    }
}
