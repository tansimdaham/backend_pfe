<?php

namespace App\Entity;

use App\Entity\Quiz;
use App\Repository\CompetenceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CompetenceRepository::class)]
class Competence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['competence:read', 'quiz:read', 'sous_competence:read', 'action:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 250)]
    #[Groups(['competence:read', 'quiz:read', 'sous_competence:read', 'action:read'])]
    private ?string $nom_fr = null;

    #[ORM\Column(length: 250)]
    #[Groups(['competence:read', 'quiz:read', 'sous_competence:read', 'action:read'])]
    private ?string $nom_en = null;

    #[ORM\Column(length: 250, nullable: true)]
    #[Groups(['competence:read', 'quiz:read'])]
    private ?string $categorie_fr = null;

    #[ORM\Column(length: 250, nullable: true)]
    #[Groups(['competence:read', 'quiz:read'])]
    private ?string $categorie_en = null;

    #[ORM\Column(name: 'idmodule', length: 50)]
    #[Groups(['competence:read', 'quiz:read', 'sous_competence:read', 'action:read'])]
    private ?string $idmodule = null;

    #[ORM\ManyToOne(inversedBy: 'competences')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Quiz $quiz = null;

    /**
     * @var Collection<int, SousCompetence>
     */
    #[ORM\OneToMany(targetEntity: SousCompetence::class, mappedBy: 'competence', cascade: ['persist', 'remove'])]
    #[Groups(['competence:read', 'quiz:read'])]
    private Collection $sousCompetences;

    public function __construct()
    {
        $this->sousCompetences = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomFr(): ?string
    {
        return $this->nom_fr;
    }

    public function setNomFr(string $nom_fr): static
    {
        $this->nom_fr = $nom_fr;

        return $this;
    }

    public function getNomEn(): ?string
    {
        return $this->nom_en;
    }

    public function setNomEn(string $nom_en): static
    {
        $this->nom_en = $nom_en;

        return $this;
    }

    public function getCategorieFr(): ?string
    {
        return $this->categorie_fr;
    }

    public function setCategorieFr(?string $categorie_fr): static
    {
        $this->categorie_fr = $categorie_fr;

        return $this;
    }

    public function getCategorieEn(): ?string
    {
        return $this->categorie_en;
    }

    public function setCategorieEn(?string $categorie_en): static
    {
        $this->categorie_en = $categorie_en;

        return $this;
    }

    public function getIdmodule(): ?string
    {
        return $this->idmodule;
    }

    public function setIdmodule(?string $idmodule): static
    {
        // Vérifier que idmodule n'est pas vide
        if (empty($idmodule)) {
            throw new \InvalidArgumentException("Le champ idmodule ne peut pas être vide");
        }

        $this->idmodule = $idmodule;
        return $this;
    }

    /**
     * @return Collection<int, SousCompetence>
     */
    public function getSousCompetences(): Collection
    {
        return $this->sousCompetences;
    }

    public function addSousCompetence(SousCompetence $sousCompetence): static
    {
        if (!$this->sousCompetences->contains($sousCompetence)) {
            $this->sousCompetences->add($sousCompetence);
            $sousCompetence->setCompetence($this);
        }

        return $this;
    }

    public function removeSousCompetence(SousCompetence $sousCompetence): static
    {
        if ($this->sousCompetences->removeElement($sousCompetence)) {
            // set the owning side to null (unless already changed)
            if ($sousCompetence->getCompetence() === $this) {
                $sousCompetence->setCompetence(null);
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
        if ($quiz !== null && $quiz->getIDModule() !== null) {
            $this->idmodule = $quiz->getIDModule();
        }
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
}
