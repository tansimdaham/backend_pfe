<?php

namespace App\Repository;

use App\Entity\EvaluationDetail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EvaluationDetail>
 *
 * @method EvaluationDetail|null find($id, $lockMode = null, $lockVersion = null)
 * @method EvaluationDetail|null findOneBy(array $criteria, array $orderBy = null)
 * @method EvaluationDetail[]    findAll()
 * @method EvaluationDetail[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EvaluationDetailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EvaluationDetail::class);
    }

    /**
     * Find evaluation details by evaluation ID
     */
    public function findByEvaluationId(int $evaluationId): ?EvaluationDetail
    {
        return $this->createQueryBuilder('ed')
            ->andWhere('ed.evaluation = :evaluationId')
            ->setParameter('evaluationId', $evaluationId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find evaluation details by quiz ID and apprenant ID
     */
    public function findByQuizAndApprenant(int $quizId, int $apprenantId): ?EvaluationDetail
    {
        return $this->createQueryBuilder('ed')
            ->join('ed.evaluation', 'e')
            ->andWhere('e.quiz = :quizId')
            ->andWhere('e.apprenant = :apprenantId')
            ->setParameter('quizId', $quizId)
            ->setParameter('apprenantId', $apprenantId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
