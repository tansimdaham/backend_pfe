<?php

namespace App\Entity;

use App\Repository\SousCompetenceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SousCompetenceRepository::class)]
class SousCompetence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['sous_competence:read', 'competence:read', 'quiz:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 250)]
    #[Groups(['sous_competence:read', 'competence:read', 'quiz:read'])]
    private ?string $nom_fr = null;

    #[ORM\Column(length: 250)]
    #[Groups(['sous_competence:read', 'competence:read', 'quiz:read'])]
    private ?string $nom_en = null;

    #[ORM\ManyToOne(inversedBy: 'sousCompetences')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['sous_competence:read'])]
    private ?Competence $competence = null;

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

    public function getCompetence(): ?Competence
    {
        return $this->competence;
    }

    public function setCompetence(?Competence $competence): static
    {
        $this->competence = $competence;

        return $this;
    }
}
