<?php

namespace App\Entity;

use App\Repository\ApprenantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApprenantRepository::class)]
class Apprenant extends Utilisateur
{
    /**
     * @var Collection<int, Administrateur>
     */
    #[ORM\ManyToMany(targetEntity: Administrateur::class, mappedBy: 'apprenant')]
    private Collection $administrateurs;

    /**
     * @var Collection<int, Reclamation>
     */
    #[ORM\OneToMany(targetEntity: Reclamation::class, mappedBy: 'apprenant')]
    private Collection $Reclamation;

    /**
     * @var Collection<int, Messagerie>
     */
    #[ORM\OneToMany(targetEntity: Messagerie::class, mappedBy: 'apprenant')]
    private Collection $Messagerie;

    /**
     * @var Collection<int, Cours>
     */
    #[ORM\ManyToMany(targetEntity: Cours::class, inversedBy: 'apprenants')]
    private Collection $cours;

    // Removed OneToOne relationship with Certificat
    // Now Certificat is the owning side with a ManyToOne relationship

    public function __construct()
    {
        $this->administrateurs = new ArrayCollection();
        $this->Reclamation = new ArrayCollection();
        $this->Messagerie = new ArrayCollection();
        $this->cours = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return parent::getId();
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
            $administrateur->addApprenant($this);
        }

        return $this;
    }

    public function removeAdministrateur(Administrateur $administrateur): static
    {
        if ($this->administrateurs->removeElement($administrateur)) {
            $administrateur->removeApprenant($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Reclamation>
     */
    public function getReclamation(): Collection
    {
        return $this->Reclamation;
    }

    public function addReclamation(Reclamation $reclamation): static
    {
        if (!$this->Reclamation->contains($reclamation)) {
            $this->Reclamation->add($reclamation);
            $reclamation->setApprenant($this);
        }

        return $this;
    }

    public function removeReclamation(Reclamation $reclamation): static
    {
        if ($this->Reclamation->removeElement($reclamation)) {
            // set the owning side to null (unless already changed)
            if ($reclamation->getApprenant() === $this) {
                $reclamation->setApprenant(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Messagerie>
     */
    public function getMessagerie(): Collection
    {
        return $this->Messagerie;
    }

    public function addMessagerie(Messagerie $messagerie): static
    {
        if (!$this->Messagerie->contains($messagerie)) {
            $this->Messagerie->add($messagerie);
            $messagerie->setApprenant($this);
        }

        return $this;
    }

    public function removeMessagerie(Messagerie $messagerie): static
    {
        if ($this->Messagerie->removeElement($messagerie)) {
            // set the owning side to null (unless already changed)
            if ($messagerie->getApprenant() === $this) {
                $messagerie->setApprenant(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Cours>
     */
    public function getCours(): Collection
    {
        return $this->cours;
    }

    public function addCour(Cours $cour): static
    {
        if (!$this->cours->contains($cour)) {
            $this->cours->add($cour);
        }

        return $this;
    }

    public function removeCour(Cours $cour): static
    {
        $this->cours->removeElement($cour);

        return $this;
    }

    // Removed getCertificat and setCertificat methods
    // Now Certificat is the owning side with a ManyToOne relationship

    /**
     * Returns the Apprenant instance as a Utilisateur
     * This method is needed for compatibility with code that expects a getUtilisateur method
     */
    public function getUtilisateur(): Utilisateur
    {
        return $this;
    }
}
