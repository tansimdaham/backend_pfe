<?php

namespace App\Repository;

use App\Entity\ChatbotConversation;
use App\Entity\Apprenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChatbotConversation>
 *
 * @method ChatbotConversation|null find($id, $lockMode = null, $lockVersion = null)
 * @method ChatbotConversation|null findOneBy(array $criteria, array $orderBy = null)
 * @method ChatbotConversation[]    findAll()
 * @method ChatbotConversation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChatbotConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatbotConversation::class);
    }

    /**
     * Récupère l'historique des conversations pour un apprenant
     *
     * @param Apprenant $apprenant
     * @param int $limit Nombre maximum de conversations à récupérer
     * @return ChatbotConversation[]
     */
    public function findByApprenant(Apprenant $apprenant, int $limit = 20): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.apprenant = :apprenant')
            ->setParameter('apprenant', $apprenant)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les dernières conversations pour un apprenant
     *
     * @param Apprenant $apprenant
     * @param int $limit Nombre maximum de conversations à récupérer
     * @return ChatbotConversation[]
     */
    public function findRecentByApprenant(Apprenant $apprenant, int $limit = 10): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.apprenant = :apprenant')
            ->setParameter('apprenant', $apprenant)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche dans les conversations par mot-clé
     *
     * @param Apprenant $apprenant
     * @param string $keyword
     * @return ChatbotConversation[]
     */
    public function searchByKeyword(Apprenant $apprenant, string $keyword): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.apprenant = :apprenant')
            ->andWhere('c.userMessage LIKE :keyword OR c.aiResponse LIKE :keyword')
            ->setParameter('apprenant', $apprenant)
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Supprime toutes les conversations d'un apprenant
     *
     * @param Apprenant $apprenant
     * @return int Nombre de conversations supprimées
     */
    public function deleteAllForApprenant(Apprenant $apprenant): int
    {
        return $this->createQueryBuilder('c')
            ->delete()
            ->andWhere('c.apprenant = :apprenant')
            ->setParameter('apprenant', $apprenant)
            ->getQuery()
            ->execute();
    }
}
