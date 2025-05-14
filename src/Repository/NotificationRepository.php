<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * Trouve les notifications d'un utilisateur, triées par date de création
     *
     * @param Utilisateur $user L'utilisateur
     * @param int|null $limit Nombre maximum de notifications à retourner
     * @return Notification[] Tableau de notifications
     */
    public function findByUser(Utilisateur $user, ?int $limit = null): array
    {
        try {
            error_log('NotificationRepository::findByUser - Début de la méthode pour utilisateur ID=' . $user->getId());

            $qb = $this->createQueryBuilder('n')
                ->where('n.user = :user')
                ->setParameter('user', $user)
                ->orderBy('n.createdAt', 'DESC');

            if ($limit) {
                $qb->setMaxResults($limit);
                error_log('NotificationRepository::findByUser - Limite définie: ' . $limit);
            }

            $query = $qb->getQuery();
            error_log('NotificationRepository::findByUser - Requête DQL: ' . $query->getDQL());

            $result = $query->getResult();
            error_log('NotificationRepository::findByUser - Nombre de résultats: ' . count($result));

            return $result;
        } catch (\Exception $e) {
            error_log('NotificationRepository::findByUser - ERREUR: ' . $e->getMessage());
            error_log('NotificationRepository::findByUser - Trace: ' . $e->getTraceAsString());
            // En cas d'erreur, retourner un tableau vide
            return [];
        }
    }

    /**
     * Trouve les notifications non lues d'un utilisateur
     *
     * @param Utilisateur $user L'utilisateur
     * @return Notification[] Tableau de notifications non lues
     */
    public function findUnreadByUser(Utilisateur $user): array
    {
        try {
            error_log('NotificationRepository::findUnreadByUser - Début de la méthode pour utilisateur ID=' . $user->getId());

            $qb = $this->createQueryBuilder('n')
                ->where('n.user = :user')
                ->andWhere('(n.read = :read OR n.read IS NULL)')
                ->setParameter('user', $user)
                ->setParameter('read', false)
                ->orderBy('n.createdAt', 'DESC');

            $query = $qb->getQuery();
            error_log('NotificationRepository::findUnreadByUser - Requête DQL: ' . $query->getDQL());

            $result = $query->getResult();
            error_log('NotificationRepository::findUnreadByUser - Nombre de résultats: ' . count($result));

            return $result;
        } catch (\Exception $e) {
            error_log('NotificationRepository::findUnreadByUser - ERREUR: ' . $e->getMessage());
            error_log('NotificationRepository::findUnreadByUser - Trace: ' . $e->getTraceAsString());
            // En cas d'erreur, retourner un tableau vide
            return [];
        }
    }

    /**
     * Compte le nombre de notifications non lues d'un utilisateur
     *
     * @param Utilisateur $user L'utilisateur
     * @return int Nombre de notifications non lues
     */
    public function countUnreadByUser(Utilisateur $user): int
    {
        try {
            error_log('NotificationRepository::countUnreadByUser - Début de la méthode pour utilisateur ID=' . $user->getId());

            $qb = $this->createQueryBuilder('n')
                ->select('COUNT(n.id)')
                ->where('n.user = :user')
                ->andWhere('(n.read = :read OR n.read IS NULL)')
                ->setParameter('user', $user)
                ->setParameter('read', false);

            $query = $qb->getQuery();
            error_log('NotificationRepository::countUnreadByUser - Requête DQL: ' . $query->getDQL());

            $result = $query->getSingleScalarResult();
            error_log('NotificationRepository::countUnreadByUser - Résultat: ' . $result);

            return (int)$result;
        } catch (\Exception $e) {
            error_log('NotificationRepository::countUnreadByUser - ERREUR: ' . $e->getMessage());
            error_log('NotificationRepository::countUnreadByUser - Trace: ' . $e->getTraceAsString());
            // En cas d'erreur, retourner 0
            return 0;
        }
    }
}
