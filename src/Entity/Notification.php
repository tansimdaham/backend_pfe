<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['notification:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['notification:read'])]
    private ?string $Description = null;

    #[ORM\ManyToOne(inversedBy: 'notifications')]
    #[ORM\JoinColumn(nullable: true)]
    private ?certificat $certificat = null;

    #[ORM\ManyToOne(inversedBy: 'notifications')]
    #[ORM\JoinColumn(nullable: true)]
    private ?reclamation $reclamation = null;

    #[ORM\ManyToOne(inversedBy: 'notifications')]
    #[ORM\JoinColumn(nullable: true)]
    private ?messagerie $messagerie = null;

    #[ORM\ManyToOne(inversedBy: 'notifications')]
    #[ORM\JoinColumn(nullable: true)]
    private ?evaluation $evaluation = null;

    #[ORM\ManyToOne(inversedBy: 'notifications')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Evenement $evenement = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    #[Groups(['notification:read'])]
    private ?Utilisateur $user = null;

    #[ORM\Column(name: "is_read", nullable: true)]
    #[Groups(['notification:read'])]
    private ?bool $read = false;

    #[ORM\Column(nullable: true)]
    #[Groups(['notification:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['notification:read'])]
    private ?string $type = null;

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

    public function getEvenement(): ?Evenement
    {
        return $this->evenement;
    }

    public function setEvenement(?Evenement $evenement): static
    {
        $this->evenement = $evenement;

        return $this;
    }

    public function getUser(): ?Utilisateur
    {
        return $this->user;
    }

    public function setUser(?Utilisateur $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function isRead(): ?bool
    {
        return $this->read;
    }

    public function setRead(?bool $read): static
    {
        $this->read = $read;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Détermine automatiquement le type de notification en fonction des relations
     * @return string Le type de notification
     */
    public function determineType(): string
    {
        if ($this->messagerie) return 'message';
        if ($this->reclamation) return 'reclamation';
        if ($this->certificat) return 'certificat';
        if ($this->evaluation) return 'evaluation';
        if ($this->evenement) return 'evenement';

        // Déterminer le type à partir de la description
        if ($this->Description) {
            $desc = strtolower($this->Description);
            if (strpos($desc, 'message') !== false || strpos($desc, 'conversation') !== false)
                return 'message';
            if (strpos($desc, 'réclamation') !== false || strpos($desc, 'reclamation') !== false)
                return 'reclamation';
            if (strpos($desc, 'certificat') !== false)
                return 'certificat';
            if (strpos($desc, 'évaluation') !== false || strpos($desc, 'evaluation') !== false)
                return 'evaluation';
            if (strpos($desc, 'événement') !== false || strpos($desc, 'evenement') !== false)
                return 'evenement';
        }

        return 'system';
    }

    /**
     * Convertit la notification en tableau pour l'envoi via WebSocket
     * @return array Données de la notification formatées pour WebSocket
     */
    public function toWebSocketArray(): array
    {
        // Déterminer le type si non défini
        if (!$this->type) {
            $this->type = $this->determineType();
        }

        return [
            'id' => $this->id,
            'Description' => $this->Description,
            'read' => $this->read,
            'createdAt' => $this->createdAt ? $this->createdAt->format('Y-m-d H:i:s') : null,
            'type' => $this->type
        ];
    }

    public function __construct()
    {
        // Initialiser les champs obligatoires
        $this->createdAt = new \DateTimeImmutable();
        $this->read = false;
        $this->Description = ''; // Initialiser avec une chaîne vide
    }
}
