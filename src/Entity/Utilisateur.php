<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\Table(name: "utilisateur")]
#[ORM\InheritanceType("JOINED")]
#[ORM\DiscriminatorColumn(name: "discr", type: "string")]
#[ORM\DiscriminatorMap([
    "utilisateur" => "Utilisateur",
    "administrateur" => "Administrateur",
    "apprenant" => "Apprenant",
    "formateur" => "Formateur"
])]
abstract class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read', 'reclamation:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Groups(['user:read', 'reclamation:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Groups(['user:read', 'reclamation:read'])]
    private ?string $email = null;

    #[ORM\Column]
    #[Groups(['user:read', 'reclamation:read'])]
    private ?int $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read', 'reclamation:read'])]
    private ?string $profileImage = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    // Ces champs ne sont pas mappés à la base de données
    private ?string $currentPassword = null;
    private ?string $newPassword = null;

    #[ORM\Column(length: 50)]
    #[Groups(['user:read', 'reclamation:read'])]
    private ?string $role = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['user:read', 'reclamation:read'])]
    private bool $isApproved = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $resetTokenExpiresAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?int
    {
        return $this->phone;
    }

    public function setPhone(int $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getProfileImage(): ?string
    {
        return $this->profileImage;
    }

    public function setProfileImage(?string $profileImage): static
    {
        $this->profileImage = $profileImage;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getCurrentPassword(): ?string
    {
        return $this->currentPassword;
    }

    public function setCurrentPassword(?string $currentPassword): static
    {
        $this->currentPassword = $currentPassword;

        return $this;
    }

    public function getNewPassword(): ?string
    {
        return $this->newPassword;
    }

    public function setNewPassword(?string $newPassword): static
    {
        $this->newPassword = $newPassword;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    /**
     * @return array<string>
     */
    public function getRoles(): array
    {
        // Commencer avec les rôles stockés dans la base de données
        $roles = $this->roles;

        // Ajouter le rôle basé sur le champ 'role' (administrateur, formateur, apprenant)
        $roleBasedOnType = 'ROLE_' . strtoupper($this->role);
        $roles[] = $roleBasedOnType;

        // Garantir que chaque utilisateur a au moins ROLE_USER
        $roles[] = 'ROLE_USER';

        // Retourner les rôles uniques
        return array_unique($roles);
    }

    /**
     * @param array<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * Retourne l'identifiant utilisé pour l'authentification
     */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @deprecated Utilisez getUserIdentifier() à la place
     */
    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    /**
     * Efface les données sensibles temporaires
     */
    public function eraseCredentials(): void
    {
        $this->currentPassword = null;
        $this->newPassword = null;
    }

    public function isApproved(): bool
    {
        return $this->isApproved;
    }

    public function setIsApproved(bool $isApproved): static
    {
        $this->isApproved = $isApproved;

        return $this;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): static
    {
        $this->resetToken = $resetToken;

        return $this;
    }

    public function getResetTokenExpiresAt(): ?\DateTimeInterface
    {
        return $this->resetTokenExpiresAt;
    }

    public function setResetTokenExpiresAt(?\DateTimeInterface $resetTokenExpiresAt): static
    {
        $this->resetTokenExpiresAt = $resetTokenExpiresAt;

        return $this;
    }
}
