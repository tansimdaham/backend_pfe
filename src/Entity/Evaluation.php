<?php

namespace App\Entity;

use App\Repository\EvaluationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EvaluationRepository::class)]
#[ORM\Index(columns: ["idmodule"], name: "IDX_EVALUATION_IDMODULE")]
class Evaluation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $StatutEvaluation = null;

    #[ORM\ManyToOne(inversedBy: 'evaluation')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Formateur $formateur = null;

    /**
     * @var Collection<int, Progression>
     */
    #[ORM\OneToMany(targetEntity: Progression::class, mappedBy: 'evaluation')]
    private Collection $progressions;

    /**
     * @var Collection<int, Notification>
     */
    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'evaluation')]
    private Collection $notifications;

    #[ORM\ManyToOne(inversedBy: 'Evaluation')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Quiz $quiz = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $idmodule = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Apprenant $apprenant = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, options: ["default" => "CURRENT_TIMESTAMP"])]
    private ?\DateTimeInterface $createdAt = null;

    /**
     * @var Collection<int, EvaluationDetail>
     */
    #[ORM\OneToMany(targetEntity: EvaluationDetail::class, mappedBy: 'evaluation', cascade: ['persist', 'remove'])]
    private Collection $evaluationDetails;

    public function __construct()
    {
        $this->progressions = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->evaluationDetails = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatutEvaluation(): ?string
    {
        return $this->StatutEvaluation;
    }

    public function setStatutEvaluation(string $StatutEvaluation): static
    {
        $this->StatutEvaluation = $StatutEvaluation;

        return $this;
    }

    public function getFormateur(): ?Formateur
    {
        return $this->formateur;
    }

    public function setFormateur(?Formateur $formateur): static
    {
        $this->formateur = $formateur;

        return $this;
    }

    /**
     * @return Collection<int, Progression>
     */
    public function getProgressions(): Collection
    {
        return $this->progressions;
    }

    public function addProgression(Progression $progression): static
    {
        if (!$this->progressions->contains($progression)) {
            $this->progressions->add($progression);
            $progression->setEvaluation($this);
        }

        return $this;
    }

    public function removeProgression(Progression $progression): static
    {
        if ($this->progressions->removeElement($progression)) {
            // set the owning side to null (unless already changed)
            if ($progression->getEvaluation() === $this) {
                $progression->setEvaluation(null);
            }
        }

        return $this;
    }



    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setEvaluation($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getEvaluation() === $this) {
                $notification->setEvaluation(null);
            }
        }

        return $this;
    }

    public function getQuiz(): ?Quiz
    {
        return $this->quiz;
    }

    public function setQuiz(?Quiz $quiz): static
    {
        $this->quiz = $quiz;

        // Mettre à jour automatiquement idmodule si le quiz est défini
        if ($quiz !== null && $quiz->getIDModule() !== null) {
            $this->idmodule = $quiz->getIDModule();
        }

        return $this;
    }

    public function getIdmodule(): ?string
    {
        return $this->idmodule;
    }

    public function setIdmodule(?string $idmodule): static
    {
        $this->idmodule = $idmodule;

        return $this;
    }

    /**
     * Synchronise le champ idmodule avec l'IDModule du quiz associé
     * Cette méthode est utile pour s'assurer que idmodule est toujours à jour
     */
    public function synchronizeIdmodule(): void
    {
        if ($this->quiz !== null && $this->quiz->getIDModule() !== null) {
            $this->idmodule = $this->quiz->getIDModule();
        }
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

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

    /**
     * @return Collection<int, EvaluationDetail>
     */
    public function getEvaluationDetails(): Collection
    {
        return $this->evaluationDetails;
    }

    public function addEvaluationDetail(EvaluationDetail $evaluationDetail): static
    {
        if (!$this->evaluationDetails->contains($evaluationDetail)) {
            $this->evaluationDetails->add($evaluationDetail);
            $evaluationDetail->setEvaluation($this);
        }

        return $this;
    }

    public function removeEvaluationDetail(EvaluationDetail $evaluationDetail): static
    {
        if ($this->evaluationDetails->removeElement($evaluationDetail)) {
            // set the owning side to null (unless already changed)
            if ($evaluationDetail->getEvaluation() === $this) {
                $evaluationDetail->setEvaluation(null);
            }
        }

        return $this;
    }
}
