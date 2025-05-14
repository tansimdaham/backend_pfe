<?php

namespace App\Entity;

use App\Repository\QuizRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: QuizRepository::class)]
class Quiz
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['quiz:read', 'competence:read', 'action:read'])]
    private ?int $id = null;

    #[ORM\Column(name: 'idmodule', type: 'string', length: 50, unique: true)]
    #[Groups(['quiz:read', 'competence:read', 'action:read'])]
    private ?string $IDModule = null;

    #[ORM\Column(length: 50)]
    #[Groups(['quiz:read', 'competence:read', 'action:read'])]
    private ?string $Category = null;

    #[ORM\Column(length: 50)]
    #[Groups(['quiz:read', 'competence:read', 'action:read'])]
    private ?string $Type = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['quiz:read', 'competence:read', 'action:read'])]
    private bool $MainSurface = false;

    #[ORM\Column]
    #[Groups(['quiz:read', 'competence:read', 'action:read'])]
    private ?int $Surface = 0;

    #[ORM\Column]
    #[Groups(['quiz:read', 'competence:read', 'action:read'])]
    private ?int $Main = 0;

    #[ORM\Column(length: 250)]
    #[Groups(['quiz:read', 'competence:read', 'action:read'])]
    private ?string $Nom_FR = null;

    #[ORM\Column(length: 250)]
    #[Groups(['quiz:read', 'competence:read', 'action:read'])]
    private ?string $Nom_EN = null;

    #[ORM\Column(length: 250, nullable: true)]
    #[Groups(['quiz:read'])]
    private ?string $PointFort_FR = null;

    #[ORM\Column(length: 250, nullable: true)]
    #[Groups(['quiz:read'])]
    private ?string $PointFort_EN = null;

    #[ORM\ManyToOne(inversedBy: 'quizzes')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['quiz:read'])]
    private ?Cours $cours = null;

    /**
     * @var Collection<int, Evaluation>
     */
    #[ORM\OneToMany(targetEntity: Evaluation::class, mappedBy: 'quiz')]
    private Collection $Evaluation;

    /**
     * @var Collection<int, Competence>
     */
    #[ORM\OneToMany(targetEntity: Competence::class, mappedBy: 'quiz', cascade: ['persist', 'remove'])]
    #[Groups(['quiz:read'])]
    private Collection $competences;

    /**
     * @var Collection<int, Action>
     */
    #[ORM\OneToMany(targetEntity: Action::class, mappedBy: 'quiz', cascade: ['persist', 'remove'])]
    #[Groups(['quiz:read'])]
    private Collection $actions;

    public function __construct()
    {
        $this->Evaluation = new ArrayCollection();
        $this->competences = new ArrayCollection();
        $this->actions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIDModule(): ?string
    {
        return $this->IDModule;
    }

    public function setIDModule(string $IDModule): static
    {
        if (empty($IDModule)) {
            throw new \InvalidArgumentException("IDModule cannot be empty");
        }

        $this->IDModule = $IDModule;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->Category;
    }

    public function setCategory(string $Category): static
    {
        $this->Category = $Category;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->Type;
    }

    public function setType(string $Type): static
    {
        $this->Type = $Type;
        return $this;
    }

    public function isMainSurface(): bool
    {
        return $this->MainSurface;
    }

    public function setMainSurface(bool $MainSurface): static
    {
        $this->MainSurface = $MainSurface;
        return $this;
    }

    public function getSurface(): ?int
    {
        return $this->Surface;
    }

    public function setSurface(int $Surface): static
    {
        $this->Surface = $Surface;
        return $this;
    }

    public function getMain(): ?int
    {
        return $this->Main;
    }

    public function setMain(int $Main): static
    {
        $this->Main = $Main;
        return $this;
    }

    public function getNomFR(): ?string
    {
        return $this->Nom_FR;
    }

    public function setNomFR(string $Nom_FR): static
    {
        $this->Nom_FR = $Nom_FR;
        return $this;
    }

    public function getNomEN(): ?string
    {
        return $this->Nom_EN;
    }

    public function setNomEN(string $Nom_EN): static
    {
        $this->Nom_EN = $Nom_EN;
        return $this;
    }

    public function getPointFortFR(): ?string
    {
        return $this->PointFort_FR;
    }

    public function setPointFortFR(?string $PointFort_FR): static
    {
        $this->PointFort_FR = $PointFort_FR;
        return $this;
    }

    public function getPointFortEN(): ?string
    {
        return $this->PointFort_EN;
    }

    public function setPointFortEN(?string $PointFort_EN): static
    {
        $this->PointFort_EN = $PointFort_EN;
        return $this;
    }

    // Méthodes supprimées - utiliser idmodule à la place

    public function getCours(): ?Cours
    {
        return $this->cours;
    }

    public function setCours(?Cours $cours): static
    {
        $this->cours = $cours;
        return $this;
    }

    /**
     * @return Collection<int, Evaluation>
     */
    public function getEvaluation(): Collection
    {
        return $this->Evaluation;
    }

    public function addEvaluation(Evaluation $evaluation): static
    {
        if (!$this->Evaluation->contains($evaluation)) {
            $this->Evaluation->add($evaluation);
            $evaluation->setQuiz($this);
        }

        return $this;
    }

    public function removeEvaluation(Evaluation $evaluation): static
    {
        if ($this->Evaluation->removeElement($evaluation)) {
            // set the owning side to null (unless already changed)
            if ($evaluation->getQuiz() === $this) {
                $evaluation->setQuiz(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Competence>
     */
    public function getCompetences(): Collection
    {
        return $this->competences;
    }

    public function addCompetence(Competence $competence): static
    {
        if (!$this->competences->contains($competence)) {
            $this->competences->add($competence);
            $competence->setQuiz($this);
        }

        return $this;
    }

    public function removeCompetence(Competence $competence): static
    {
        if ($this->competences->removeElement($competence)) {
            // set the owning side to null (unless already changed)
            if ($competence->getQuiz() === $this) {
                $competence->setQuiz(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Action>
     */
    public function getActions(): Collection
    {
        return $this->actions;
    }

    public function addAction(Action $action): static
    {
        if (!$this->actions->contains($action)) {
            $this->actions->add($action);
            $action->setQuiz($this);
        }

        return $this;
    }

    public function removeAction(Action $action): static
    {
        if ($this->actions->removeElement($action)) {
            // set the owning side to null (unless already changed)
            if ($action->getQuiz() === $this) {
                $action->setQuiz(null);
            }
        }

        return $this;
    }
}