<?php

namespace App\Entity;

use App\Repository\FormateurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FormateurRepository::class)]
class Formateur extends Utilisateur
{
    /**
     * @var Collection<int, Messagerie>
     */
    #[ORM\OneToMany(targetEntity: Messagerie::class, mappedBy: 'formateur')]
    private Collection $messenger;

    /**
     * @var Collection<int, evaluation>
     */
    #[ORM\OneToMany(targetEntity: Evaluation::class, mappedBy: 'formateur')]
    private Collection $evaluation;

    public function __construct()
    {
        $this->messenger = new ArrayCollection();
        $this->evaluation = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return parent::getId();
    }

    /**
     * @return Collection<int, Messagerie>
     */
    public function getMessenger(): Collection
    {
        return $this->messenger;
    }

    public function addMessenger(Messagerie $messenger): static
    {
        if (!$this->messenger->contains($messenger)) {
            $this->messenger->add($messenger);
            $messenger->setFormateur($this);
        }

        return $this;
    }

    public function removeMessenger(Messagerie $messenger): static
    {
        if ($this->messenger->removeElement($messenger)) {
            // set the owning side to null (unless already changed)
            if ($messenger->getFormateur() === $this) {
                $messenger->setFormateur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, evaluation>
     */
    public function getEvaluation(): Collection
    {
        return $this->evaluation;
    }

    public function addEvaluation(Evaluation $evaluation): static
    {
        if (!$this->evaluation->contains($evaluation)) {
            $this->evaluation->add($evaluation);
            $evaluation->setFormateur($this);
        }

        return $this;
    }

    public function removeEvaluation(Evaluation $evaluation): static
    {
        if ($this->evaluation->removeElement($evaluation)) {
            // set the owning side to null (unless already changed)
            if ($evaluation->getFormateur() === $this) {
                $evaluation->setFormateur(null);
            }
        }

        return $this;
    }
}
