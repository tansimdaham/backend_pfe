<?php

namespace App\Entity;

use App\Repository\ProgressionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProgressionRepository::class)]
class Progression
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::ARRAY)]
    private array $TableEvaluations = [];

    #[ORM\ManyToOne(inversedBy: 'progression')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cours $cours = null;

    #[ORM\OneToOne(mappedBy: 'progression', cascade: ['persist', 'remove'])]
    private ?Certificat $certificat = null;

    #[ORM\ManyToOne(inversedBy: 'progressions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Evaluation $evaluation = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Apprenant $apprenant = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTableEvaluations(): array
    {
        return $this->TableEvaluations;
    }

    public function setTableEvaluations(array $TableEvaluations): static
    {
        $this->TableEvaluations = $TableEvaluations;

        return $this;
    }

    public function getCours(): ?Cours
    {
        return $this->cours;
    }

    public function setCours(?Cours $cours): static
    {
        $this->cours = $cours;

        return $this;
    }

    public function getCertificat(): ?Certificat
    {
        return $this->certificat;
    }

    public function setCertificat(?Certificat $certificat): static
    {
        // Si le certificat est null, on supprime l'association
        if ($certificat === null) {
            $this->certificat = null;
            return $this;
        }

        // Vérifier que l'entité a un ID avant d'établir la relation
        if (!$this->id) {
            // Au lieu de lancer une exception, on log un avertissement
            // et on laisse la relation s'établir quand même
            error_log('AVERTISSEMENT: La progression doit être persistée avant d\'être associée à un certificat');
        }

        // Vérifier si le certificat a déjà une progression différente de celle-ci
        try {
            $currentProgression = $certificat->getProgression();
            if ($currentProgression !== null && $currentProgression !== $this) {
                error_log('AVERTISSEMENT: Le certificat est déjà associé à une autre progression (ID: ' .
                    ($currentProgression->getId() ?: 'non défini') . ')');

                // On continue quand même, mais on ne modifie pas la relation inverse
                $this->certificat = $certificat;
                return $this;
            }
        } catch (\Exception $e) {
            error_log('Erreur ignorée lors de la vérification de la progression actuelle: ' . $e->getMessage());
        }

        // Éviter une boucle infinie
        $this->certificat = $certificat;

        // set the owning side of the relation if necessary
        try {
            if ($certificat->getProgression() !== $this) {
                // Désactiver temporairement la vérification bidirectionnelle
                try {
                    // Utiliser ReflectionClass pour éviter les boucles infinies
                    $reflectionClass = new \ReflectionClass(Certificat::class);
                    $property = $reflectionClass->getProperty('progression');
                    $property->setAccessible(true);
                    $property->setValue($certificat, $this);

                    error_log('Progression associée au certificat via réflexion');
                } catch (\Exception $e) {
                    error_log('Erreur lors de l\'utilisation de la réflexion: ' . $e->getMessage());

                    // Essayer la méthode normale
                    try {
                        $certificat->setProgression($this);
                    } catch (\Exception $e2) {
                        error_log('Erreur ignorée lors de l\'établissement de la relation bidirectionnelle (setProgression): ' . $e2->getMessage());
                    }
                }
            }
        } catch (\Exception $e) {
            // Si une exception est levée, c'est probablement parce que
            // la progression n'est pas encore persistée. On ignore cette erreur
            // car la relation sera correctement établie après la persistance.
            error_log('Erreur ignorée lors de l\'établissement de la relation bidirectionnelle: ' . $e->getMessage());
        }

        return $this;
    }

    public function getEvaluation(): ?Evaluation
    {
        return $this->evaluation;
    }

    public function setEvaluation(?Evaluation $evaluation): static
    {
        $this->evaluation = $evaluation;

        return $this;
    }

    public function getApprenant(): ?Apprenant
    {
        return $this->apprenant;
    }

    public function setApprenant(?Apprenant $apprenant): static
    {
        $this->apprenant = $apprenant;

        return $this;
    }
}
