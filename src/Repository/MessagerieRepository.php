<?php

namespace App\Repository;

use App\Entity\Messagerie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Messagerie>
 */
class MessagerieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Messagerie::class);
    }

    /**
     * Trouve tous les messages entre un formateur et un apprenant
     *
     * @param int $formateurId
     * @param int $apprenantId
     * @return Messagerie[] Returns an array of Messagerie objects
     */
    public function findConversation(int $formateurId, int $apprenantId): array
    {
        try {
            $qb = $this->createQueryBuilder('m');
            return $qb->where(
                    $qb->expr()->andX(
                        $qb->expr()->eq('m.formateur', ':formateur'),
                        $qb->expr()->eq('m.apprenant', ':apprenant')
                    )
                )
                ->setParameter('formateur', $formateurId)
                ->setParameter('apprenant', $apprenantId)
                ->orderBy('m.date', 'ASC')
                ->getQuery()
                ->getResult()
            ;
        } catch (\Exception $e) {
            // Log the error
            error_log('Error in findConversation: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Trouve toutes les conversations d'un formateur
     *
     * @param int $formateurId
     * @return array
     */
    public function findFormateurConversations(int $formateurId): array
    {
        try {
            $em = $this->getEntityManager();

            // Requête SQL pour obtenir les derniers messages par apprenant
            $sql = "
                SELECT
                    m.id,
                    m.message,
                    m.date,
                    m.lu,
                    a.id as apprenant_id,
                    u.name as apprenant_name,
                    u.profile_image as apprenant_image,
                    (SELECT COUNT(*) FROM messagerie m2 WHERE m2.formateur_id = :formateurId AND m2.apprenant_id = a.id AND m2.lu = 0) as unread_count
                FROM messagerie m
                JOIN apprenant a ON m.apprenant_id = a.id
                JOIN utilisateur u ON a.id = u.id
                JOIN (
                    SELECT apprenant_id, MAX(date) as max_date
                    FROM messagerie
                    WHERE formateur_id = :formateurId
                    GROUP BY apprenant_id
                ) as latest ON m.apprenant_id = latest.apprenant_id AND m.date = latest.max_date
                WHERE m.formateur_id = :formateurId
                ORDER BY m.date DESC
            ";

            $stmt = $em->getConnection()->prepare($sql);
            $result = $stmt->executeQuery(['formateurId' => $formateurId]);

            return $result->fetchAllAssociative();
        } catch (\Exception $e) {
            // Log the error
            error_log('Error in findFormateurConversations: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Trouve toutes les conversations d'un apprenant
     *
     * @param int $apprenantId
     * @return array
     */
    public function findApprenantConversations(int $apprenantId): array
    {
        try {
            $em = $this->getEntityManager();

            // Requête SQL pour obtenir les derniers messages par formateur
            $sql = "
                SELECT
                    m.id,
                    m.message,
                    m.date,
                    m.lu,
                    f.id as formateur_id,
                    u.name as formateur_name,
                    u.profile_image as formateur_image,
                    (SELECT COUNT(*) FROM messagerie m2 WHERE m2.apprenant_id = :apprenantId AND m2.formateur_id = f.id AND m2.lu = 0) as unread_count
                FROM messagerie m
                JOIN formateur f ON m.formateur_id = f.id
                JOIN utilisateur u ON f.id = u.id
                JOIN (
                    SELECT formateur_id, MAX(date) as max_date
                    FROM messagerie
                    WHERE apprenant_id = :apprenantId
                    GROUP BY formateur_id
                ) as latest ON m.formateur_id = latest.formateur_id AND m.date = latest.max_date
                WHERE m.apprenant_id = :apprenantId
                ORDER BY m.date DESC
            ";

            $stmt = $em->getConnection()->prepare($sql);
            $result = $stmt->executeQuery(['apprenantId' => $apprenantId]);

            return $result->fetchAllAssociative();
        } catch (\Exception $e) {
            // Log the error
            error_log('Error in findApprenantConversations: ' . $e->getMessage());
            return [];
        }
    }
}
