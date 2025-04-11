<?php

namespace App\Entity;

use App\Repository\CoursRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CoursRepository::class)]
class Cours
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $titre = null;

    /**
     * @var Collection<int, Administrateur>
     */
    #[ORM\ManyToMany(targetEntity: Administrateur::class, mappedBy: 'cours')]
    private Collection $administrateurs;

    /**
     * @var Collection<int, Apprenant>
     */
    #[ORM\ManyToMany(targetEntity: Apprenant::class, mappedBy: 'cours')]
    private Collection $apprenants;

    /**
     * @var Collection<int, progression>
     */
    #[ORM\OneToMany(targetEntity: progression::class, mappedBy: 'cours')]
    private Collection $progression;

    /**
     * @var Collection<int, Quiz>
     */
    #[ORM\OneToMany(targetEntity: Quiz::class, mappedBy: 'cours')]
    private Collection $quizzes;

    public function __construct()
    {
        $this->administrateurs = new ArrayCollection();
        $this->apprenants = new ArrayCollection();
        $this->progression = new ArrayCollection();
        $this->quizzes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    /**
     * @return Collection<int, Administrateur>
     */
    public function getAdministrateurs(): Collection
    {
        return $this->administrateurs;
    }

    public function addAdministrateur(Administrateur $administrateur): static
    {
        if (!$this->administrateurs->contains($administrateur)) {
            $this->administrateurs->add($administrateur);
            $administrateur->addCour($this);
        }

        return $this;
    }

    public function removeAdministrateur(Administrateur $administrateur): static
    {
        if ($this->administrateurs->removeElement($administrateur)) {
            $administrateur->removeCour($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Apprenant>
     */
    public function getApprenants(): Collection
    {
        return $this->apprenants;
    }

    public function addApprenant(Apprenant $apprenant): static
    {
        if (!$this->apprenants->contains($apprenant)) {
            $this->apprenants->add($apprenant);
            $apprenant->addCour($this);
        }

        return $this;
    }

    public function removeApprenant(Apprenant $apprenant): static
    {
        if ($this->apprenants->removeElement($apprenant)) {
            $apprenant->removeCour($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, progression>
     */
    public function getProgression(): Collection
    {
        return $this->progression;
    }

    public function addProgression(progression $progression): static
    {
        if (!$this->progression->contains($progression)) {
            $this->progression->add($progression);
            $progression->setCours($this);
        }

        return $this;
    }

    public function removeProgression(progression $progression): static
    {
        if ($this->progression->removeElement($progression)) {
            // set the owning side to null (unless already changed)
            if ($progression->getCours() === $this) {
                $progression->setCours(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Quiz>
     */
    public function getQuizzes(): Collection
    {
        return $this->quizzes;
    }

    public function addQuiz(Quiz $quiz): static
    {
        if (!$this->quizzes->contains($quiz)) {
            $this->quizzes->add($quiz);
            $quiz->setCours($this);
        }

        return $this;
    }

    public function removeQuiz(Quiz $quiz): static
    {
        if ($this->quizzes->removeElement($quiz)) {
            // set the owning side to null (unless already changed)
            if ($quiz->getCours() === $this) {
                $quiz->setCours(null);
            }
        }

        return $this;
    }
}
