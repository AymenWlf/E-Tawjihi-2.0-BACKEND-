<?php

namespace App\Entity;

use App\Repository\TestAnswerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TestAnswerRepository::class)]
#[ORM\HasLifecycleCallbacks]
class TestAnswer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'answers')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?TestSession $testSession = null;

    #[ORM\Column(length: 255)]
    private ?string $questionKey = null;

    #[ORM\Column(type: Types::JSON)]
    private array $answerData = [];

    #[ORM\Column]
    private ?\DateTimeImmutable $answeredAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $stepNumber = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->answeredAt = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTestSession(): ?TestSession
    {
        return $this->testSession;
    }

    public function setTestSession(?TestSession $testSession): self
    {
        $this->testSession = $testSession;
        return $this;
    }

    public function getQuestionKey(): ?string
    {
        return $this->questionKey;
    }

    public function setQuestionKey(string $questionKey): self
    {
        $this->questionKey = $questionKey;
        return $this;
    }

    public function getAnswerData(): array
    {
        return $this->answerData;
    }

    public function setAnswerData(array $answerData): self
    {
        $this->answerData = $answerData;
        return $this;
    }

    public function getAnsweredAt(): ?\DateTimeImmutable
    {
        return $this->answeredAt;
    }

    public function setAnsweredAt(\DateTimeImmutable $answeredAt): self
    {
        $this->answeredAt = $answeredAt;
        return $this;
    }

    public function getStepNumber(): ?int
    {
        return $this->stepNumber;
    }

    public function setStepNumber(?int $stepNumber): self
    {
        $this->stepNumber = $stepNumber;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTimeImmutable();
        }
        if ($this->updatedAt === null) {
            $this->updatedAt = new \DateTimeImmutable();
        }
        if ($this->answeredAt === null) {
            $this->answeredAt = new \DateTimeImmutable();
        }
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
