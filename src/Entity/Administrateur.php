<?php

namespace App\Entity;

use App\Repository\AdministrateurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdministrateurRepository::class)]
class Administrateur extends Utilisateur
{
    /**
     * @var Collection<int, Reclamation>
     */
    #[ORM\OneToMany(targetEntity: Reclamation::class, mappedBy: 'administrateur')]
    private Collection $Reclamation;

    /**
     * @var Collection<int, cours>
     */
    #[ORM\ManyToMany(targetEntity: cours::class, inversedBy: 'administrateurs')]
    private Collection $cours;

    /**
     * @var Collection<int, apprenant>
     */
    #[ORM\ManyToMany(targetEntity: apprenant::class, inversedBy: 'administrateurs')]
    private Collection $apprenant;

    public function __construct()
    {
        $this->Reclamation = new ArrayCollection();
        $this->cours = new ArrayCollection();
        $this->apprenant = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return parent::getId();
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
            $reclamation->setAdministrateur($this);
        }

        return $this;
    }

    public function removeReclamation(Reclamation $reclamation): static
    {
        if ($this->Reclamation->removeElement($reclamation)) {
            // set the owning side to null (unless already changed)
            if ($reclamation->getAdministrateur() === $this) {
                $reclamation->setAdministrateur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, cours>
     */
    public function getCours(): Collection
    {
        return $this->cours;
    }

    public function addCour(cours $cour): static
    {
        if (!$this->cours->contains($cour)) {
            $this->cours->add($cour);
        }

        return $this;
    }

    public function removeCour(cours $cour): static
    {
        $this->cours->removeElement($cour);

        return $this;
    }

    /**
     * @return Collection<int, apprenant>
     */
    public function getApprenant(): Collection
    {
        return $this->apprenant;
    }

    public function addApprenant(apprenant $apprenant): static
    {
        if (!$this->apprenant->contains($apprenant)) {
            $this->apprenant->add($apprenant);
        }

        return $this;
    }

    public function removeApprenant(apprenant $apprenant): static
    {
        $this->apprenant->removeElement($apprenant);

        return $this;
    }
}
