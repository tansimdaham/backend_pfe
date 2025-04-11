<?php

namespace App\Entity;

use App\Repository\QuizRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuizRepository::class)]
class Quiz
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $IDModule = null;

    #[ORM\Column(length: 50)]
    private ?string $Category = null;

    #[ORM\Column(length: 50)]
    private ?string $Type = null;

    #[ORM\Column(length: 50)]
    private ?string $MainSurface = null;

    #[ORM\Column(length: 50)]
    private ?string $Vitesse = null;

    #[ORM\Column]
    private ?int $Surface = null;

    #[ORM\Column]
    private ?int $Main = null;

    #[ORM\Column(length: 50)]
    private ?string $Nom_FR = null;

    #[ORM\Column(length: 50)]
    private ?string $Nom_EN = null;

    #[ORM\Column(length: 50)]
    private ?string $PointFort_FR = null;

    #[ORM\Column(length: 50)]
    private ?string $PointFort_EN = null;

    #[ORM\Column]
    private ?int $Competence_ID = null;

    #[ORM\Column(length: 50)]
    private ?string $Comp_Categorie_FR = null;

    #[ORM\Column(length: 50)]
    private ?string $Comp_Categorie_EN = null;

    #[ORM\Column(length: 50)]
    private ?string $Competence_Nom_FR = null;

    #[ORM\Column(length: 50)]
    private ?string $Competence_Nom_EN = null;

    #[ORM\Column(length: 50)]
    private ?string $SousCompetence_Nom_FR = null;

    #[ORM\Column(length: 255)]
    private ?string $SousCompetence_Nom_EN = null;

    #[ORM\Column(length: 50)]
    private ?string $Action_Nom_FR = null;

    #[ORM\Column(length: 50)]
    private ?string $Action_Nom_EN = null;

    #[ORM\Column(length: 50)]
    private ?string $Action_Categorie_FR = null;

    #[ORM\Column(length: 50)]
    private ?string $Action_Categorie_EN = null;

    #[ORM\ManyToOne(inversedBy: 'quizzes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?cours $cours = null;

    #[ORM\ManyToOne(inversedBy: 'quizzes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?evaluation $evaluation = null;

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

    public function getMainSurface(): ?string
    {
        return $this->MainSurface;
    }

    public function setMainSurface(string $MainSurface): static
    {
        $this->MainSurface = $MainSurface;

        return $this;
    }

    public function getVitesse(): ?string
    {
        return $this->Vitesse;
    }

    public function setVitesse(string $Vitesse): static
    {
        $this->Vitesse = $Vitesse;

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

    public function setPointFortFR(string $PointFort_FR): static
    {
        $this->PointFort_FR = $PointFort_FR;

        return $this;
    }

    public function getPointFortEN(): ?string
    {
        return $this->PointFort_EN;
    }

    public function setPointFortEN(string $PointFort_EN): static
    {
        $this->PointFort_EN = $PointFort_EN;

        return $this;
    }

    public function getCompetenceID(): ?int
    {
        return $this->Competence_ID;
    }

    public function setCompetenceID(int $Competence_ID): static
    {
        $this->Competence_ID = $Competence_ID;

        return $this;
    }

    public function getCompCategorieFR(): ?string
    {
        return $this->Comp_Categorie_FR;
    }

    public function setCompCategorieFR(string $Comp_Categorie_FR): static
    {
        $this->Comp_Categorie_FR = $Comp_Categorie_FR;

        return $this;
    }

    public function getCompCategorieEN(): ?string
    {
        return $this->Comp_Categorie_EN;
    }

    public function setCompCategorieEN(string $Comp_Categorie_EN): static
    {
        $this->Comp_Categorie_EN = $Comp_Categorie_EN;

        return $this;
    }

    public function getCompetenceNomFR(): ?string
    {
        return $this->Competence_Nom_FR;
    }

    public function setCompetenceNomFR(string $Competence_Nom_FR): static
    {
        $this->Competence_Nom_FR = $Competence_Nom_FR;

        return $this;
    }

    public function getCompetenceNomEN(): ?string
    {
        return $this->Competence_Nom_EN;
    }

    public function setCompetenceNomEN(string $Competence_Nom_EN): static
    {
        $this->Competence_Nom_EN = $Competence_Nom_EN;

        return $this;
    }

    public function getSousCompetenceNomFR(): ?string
    {
        return $this->SousCompetence_Nom_FR;
    }

    public function setSousCompetenceNomFR(string $SousCompetence_Nom_FR): static
    {
        $this->SousCompetence_Nom_FR = $SousCompetence_Nom_FR;

        return $this;
    }

    public function getSousCompetenceNomEN(): ?string
    {
        return $this->SousCompetence_Nom_EN;
    }

    public function setSousCompetenceNomEN(string $SousCompetence_Nom_EN): static
    {
        $this->SousCompetence_Nom_EN = $SousCompetence_Nom_EN;

        return $this;
    }

    public function getActionNomFR(): ?string
    {
        return $this->Action_Nom_FR;
    }

    public function setActionNomFR(string $Action_Nom_FR): static
    {
        $this->Action_Nom_FR = $Action_Nom_FR;

        return $this;
    }

    public function getActionNomEN(): ?string
    {
        return $this->Action_Nom_EN;
    }

    public function setActionNomEN(string $Action_Nom_EN): static
    {
        $this->Action_Nom_EN = $Action_Nom_EN;

        return $this;
    }

    public function getActionCategorieFR(): ?string
    {
        return $this->Action_Categorie_FR;
    }

    public function setActionCategorieFR(string $Action_Categorie_FR): static
    {
        $this->Action_Categorie_FR = $Action_Categorie_FR;

        return $this;
    }

    public function getActionCategorieEN(): ?string
    {
        return $this->Action_Categorie_EN;
    }

    public function setActionCategorieEN(string $Action_Categorie_EN): static
    {
        $this->Action_Categorie_EN = $Action_Categorie_EN;

        return $this;
    }

    public function getCours(): ?cours
    {
        return $this->cours;
    }

    public function setCours(?cours $cours): static
    {
        $this->cours = $cours;

        return $this;
    }

    public function getEvaluation(): ?evaluation
    {
        return $this->evaluation;
    }

    public function setEvaluation(?evaluation $evaluation): static
    {
        $this->evaluation = $evaluation;

        return $this;
    }
}
