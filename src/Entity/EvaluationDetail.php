<?php

namespace App\Entity;

use App\Repository\EvaluationDetailRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EvaluationDetailRepository::class)]
class EvaluationDetail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'evaluationDetails')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Evaluation $evaluation = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $competenceStatuses = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $checkedSousCompetences = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $checkedActions = null;

    #[ORM\Column(nullable: true)]
    private ?float $mainValue = null;

    #[ORM\Column(nullable: true)]
    private ?float $surfaceValue = null;

    #[ORM\Column(nullable: true)]
    private ?float $originalMainValue = null;

    #[ORM\Column(nullable: true)]
    private ?float $originalSurfaceValue = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCompetenceStatuses(): ?array
    {
        return $this->competenceStatuses;
    }

    public function setCompetenceStatuses(?array $competenceStatuses): static
    {
        $this->competenceStatuses = $competenceStatuses;

        return $this;
    }

    public function getCheckedSousCompetences(): ?array
    {
        return $this->checkedSousCompetences;
    }

    public function setCheckedSousCompetences(?array $checkedSousCompetences): static
    {
        $this->checkedSousCompetences = $checkedSousCompetences;

        return $this;
    }



    public function getCheckedActions(): ?array
    {
        return $this->checkedActions;
    }

    public function setCheckedActions(?array $checkedActions): static
    {
        $this->checkedActions = $checkedActions;

        return $this;
    }

    public function getMainValue(): ?float
    {
        return $this->mainValue;
    }

    public function setMainValue(?float $mainValue): static
    {
        $this->mainValue = $mainValue;

        return $this;
    }

    public function getSurfaceValue(): ?float
    {
        return $this->surfaceValue;
    }

    public function setSurfaceValue(?float $surfaceValue): static
    {
        $this->surfaceValue = $surfaceValue;

        return $this;
    }

    public function getOriginalMainValue(): ?float
    {
        return $this->originalMainValue;
    }

    public function setOriginalMainValue(?float $originalMainValue): static
    {
        $this->originalMainValue = $originalMainValue;

        return $this;
    }

    public function getOriginalSurfaceValue(): ?float
    {
        return $this->originalSurfaceValue;
    }

    public function setOriginalSurfaceValue(?float $originalSurfaceValue): static
    {
        $this->originalSurfaceValue = $originalSurfaceValue;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
