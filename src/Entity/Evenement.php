<?php

namespace App\Entity;

use App\Repository\EvenementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: EvenementRepository::class)]
class Evenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['evenement:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['evenement:read'])]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['evenement:read'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['evenement:read'])]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['evenement:read'])]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column]
    #[Groups(['evenement:read'])]
    private bool $journeeEntiere = true;

    #[ORM\Column(length: 50)]
    #[Groups(['evenement:read'])]
    private ?string $categorie = 'evaluation';

    #[ORM\Column(length: 20)]
    #[Groups(['evenement:read'])]
    private ?string $couleur = '#EA4335';

    /**
     * @var Collection<int, Administrateur>
     */
    #[ORM\ManyToMany(targetEntity: Administrateur::class, inversedBy: 'evenements')]
    #[Groups(['evenement:read'])]
    private Collection $administrateurs;

    /**
     * @var Collection<int, Notification>
     */
    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'evenement')]
    private Collection $notifications;

    public function __construct()
    {
        $this->administrateurs = new ArrayCollection();
        $this->notifications = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function isJourneeEntiere(): bool
    {
        return $this->journeeEntiere;
    }

    public function setJourneeEntiere(bool $journeeEntiere): static
    {
        $this->journeeEntiere = $journeeEntiere;

        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(string $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(string $couleur): static
    {
        $this->couleur = $couleur;

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
        }

        return $this;
    }

    public function removeAdministrateur(Administrateur $administrateur): static
    {
        $this->administrateurs->removeElement($administrateur);

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
            $notification->setEvenement($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getEvenement() === $this) {
                $notification->setEvenement(null);
            }
        }

        return $this;
    }
}
