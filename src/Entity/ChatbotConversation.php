<?php

namespace App\Entity;

use App\Repository\ChatbotConversationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ChatbotConversationRepository::class)]
class ChatbotConversation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['chatbot:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Apprenant::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['chatbot:read'])]
    private ?Apprenant $apprenant = null;

    #[ORM\Column(type: 'text')]
    #[Groups(['chatbot:read'])]
    private string $userMessage;

    #[ORM\Column(type: 'text')]
    #[Groups(['chatbot:read'])]
    private string $aiResponse;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['chatbot:read'])]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['chatbot:read'])]
    private ?string $context = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApprenant(): ?Apprenant
    {
        return $this->apprenant;
    }

    public function setApprenant(?Apprenant $apprenant): self
    {
        $this->apprenant = $apprenant;

        return $this;
    }

    public function getUserMessage(): string
    {
        return $this->userMessage;
    }

    public function setUserMessage(string $userMessage): self
    {
        $this->userMessage = $userMessage;

        return $this;
    }

    public function getAiResponse(): string
    {
        return $this->aiResponse;
    }

    public function setAiResponse(string $aiResponse): self
    {
        $this->aiResponse = $aiResponse;

        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function setContext(?string $context): self
    {
        $this->context = $context;

        return $this;
    }
}
