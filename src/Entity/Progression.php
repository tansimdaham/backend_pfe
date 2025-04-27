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

    public function setCertificat(Certificat $certificat): static
    {
        // set the owning side of the relation if necessary
        if ($certificat->getProgression() !== $this) {
            $certificat->setProgression($this);
        }

        $this->certificat = $certificat;

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
}
