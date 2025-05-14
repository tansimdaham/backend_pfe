<?php

namespace App\Repository;

use App\Entity\Quiz;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Quiz>
 */
class QuizRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quiz::class);
    }

    /**
     * Trouve les quiz par catégorie
     *
     * @param string $category La catégorie à rechercher (ex: 'Sterile', 'Non-Sterile')
     * @return Quiz[] Retourne un tableau d'objets Quiz
     */
    public function findByCategory(string $category): array
    {
        try {
            return $this->createQueryBuilder('q')
                ->andWhere('LOWER(q.Category) = LOWER(:category)')
                ->setParameter('category', $category)
                ->orderBy('q.id', 'ASC')
                ->getQuery()
                ->getResult();
        } catch (\Exception $e) {
            error_log("QuizRepository::findByCategory - Exception: " . $e->getMessage());
            // Fallback to direct findBy method - note that this fallback won't be case-insensitive
            // but we'll keep it as a last resort
            return $this->findBy(['Category' => $category]);
        }
    }

    /**
     * Trouve les cours qui ont des quiz d'une catégorie spécifique
     *
     * @param string $category La catégorie à rechercher (ex: 'Sterile', 'Non-Sterile')
     * @return array Retourne un tableau de cours uniques qui ont des quiz de la catégorie spécifiée
     */
    public function findCoursesByQuizCategory(string $category): array
    {
        try {
            // Utiliser une requête DQL plus explicite pour déboguer avec LOWER pour ignorer la casse
            $entityManager = $this->getEntityManager();
            $dql = "SELECT DISTINCT c FROM App\Entity\Cours c JOIN c.quizzes q WHERE LOWER(q.Category) = LOWER(:category)";
            $query = $entityManager->createQuery($dql);
            $query->setParameter('category', $category);
            $result = $query->getResult();

            // Journaliser le nombre de résultats pour le débogage
            error_log("findCoursesByQuizCategory: Found " . count($result) . " courses with category " . $category);

            // Si aucun résultat n'est trouvé, essayer avec une requête SQL native
            if (empty($result)) {
                error_log("Aucun résultat trouvé avec DQL, essai avec SQL natif");
                $result = $this->findCoursesByCategoryNative($category);
            }

            return $result;
        } catch (\Exception $e) {
            error_log("QuizRepository::findCoursesByQuizCategory - Exception: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Trouve les cours qui ont des quiz d'une catégorie spécifique en utilisant SQL natif
     *
     * @param string $category La catégorie à rechercher (ex: 'Sterile', 'Non-Sterile')
     * @return array Retourne un tableau de cours uniques qui ont des quiz de la catégorie spécifiée
     */
    public function findCoursesByCategoryNative(string $category): array
    {
        try {
            $conn = $this->getEntityManager()->getConnection();

            // Requête SQL native pour récupérer les IDs des cours avec LOWER pour ignorer la casse
            $sql = "
                SELECT DISTINCT c.id, c.titre, c.description
                FROM Cours c
                JOIN Quiz q ON c.id = q.cours_id
                WHERE LOWER(q.category) = LOWER(:category)
            ";

            $stmt = $conn->prepare($sql);
            $stmt->bindValue('category', $category);
            $resultSet = $stmt->executeQuery();
            $coursIds = $resultSet->fetchAllAssociative();

            error_log("SQL natif: Trouvé " . count($coursIds) . " cours avec catégorie " . $category);

            // Si aucun cours n'est trouvé, retourner un tableau vide
            if (empty($coursIds)) {
                return [];
            }

            // Récupérer les entités Cours complètes à partir des IDs
            $coursRepository = $this->getEntityManager()->getRepository('App\Entity\Cours');
            $cours = [];

            foreach ($coursIds as $coursData) {
                $cours[] = $coursRepository->find($coursData['id']);
            }

            return $cours;
        } catch (\Exception $e) {
            error_log("QuizRepository::findCoursesByCategoryNative - Exception: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Trouve les cours qui ont des quiz de plusieurs catégories (stérile et non stérile)
     *
     * @return array Retourne un tableau de cours uniques qui ont des quiz de différentes catégories
     */
    public function findCoursesWithMultipleCategories(): array
    {
        try {
            $conn = $this->getEntityManager()->getConnection();

            // Requête SQL native pour récupérer les IDs des cours qui ont des quiz de catégorie 'Sterile' ou 'Non-Sterile'
            // Utiliser LOWER pour ignorer la casse
            $sql = "
                SELECT DISTINCT c.id, c.titre, c.description
                FROM Cours c
                JOIN Quiz q ON c.id = q.cours_id
                WHERE LOWER(q.category) IN ('sterile', 'non-sterile')
            ";

            $stmt = $conn->prepare($sql);
            $resultSet = $stmt->executeQuery();
            $coursIds = $resultSet->fetchAllAssociative();

            error_log("SQL natif: Trouvé " . count($coursIds) . " cours avec catégories 'Sterile' ou 'Non-Sterile'");

            // Si aucun cours n'est trouvé, retourner un tableau vide
            if (empty($coursIds)) {
                return [];
            }

            // Récupérer les entités Cours complètes à partir des IDs
            $coursRepository = $this->getEntityManager()->getRepository('App\Entity\Cours');
            $cours = [];

            foreach ($coursIds as $coursData) {
                $cours[] = $coursRepository->find($coursData['id']);
            }

            return $cours;
        } catch (\Exception $e) {
            error_log("QuizRepository::findCoursesWithMultipleCategories - Exception: " . $e->getMessage());
            return [];
        }
    }

//    /**
//     * @return Quiz[] Returns an array of Quiz objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('q')
//            ->andWhere('q.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('q.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Quiz
//    {
//        return $this->createQueryBuilder('q')
//            ->andWhere('q.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
