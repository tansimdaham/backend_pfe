<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $Description = null;

    #[ORM\ManyToOne(inversedBy: 'notifications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?certificat $certificat = null;

    #[ORM\ManyToOne(inversedBy: 'notifications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?reclamation $reclamation = null;

    #[ORM\ManyToOne(inversedBy: 'notifications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?messagerie $messagerie = null;

    #[ORM\ManyToOne(inversedBy: 'notifications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?evaluation $evaluation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->Description;
    }

    public function setDescription(string $Description): static
    {
        $this->Description = $Description;

        return $this;
    }

    public function getCertificat(): ?certificat
    {
        return $this->certificat;
    }

    public function setCertificat(?certificat $certificat): static
    {
        $this->certificat = $certificat;

        return $this;
    }

    public function getReclamation(): ?reclamation
    {
        return $this->reclamation;
    }

    public function setReclamation(?reclamation $reclamation): static
    {
        $this->reclamation = $reclamation;

        return $this;
    }

    public function getMessagerie(): ?messagerie
    {
        return $this->messagerie;
    }

    public function setMessagerie(?messagerie $messagerie): static
    {
        $this->messagerie = $messagerie;

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
