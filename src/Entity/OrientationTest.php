<?php

namespace App\Entity;

use App\Repository\OrientationTestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrientationTestRepository::class)]
#[ORM\HasLifecycleCallbacks]
class OrientationTest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'orientationTest', targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    // Données des étapes du test (stockées en JSON)
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $personalInfo = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $riasec = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $personality = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $aptitude = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $interests = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $career = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $constraints = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $languages = null;

    // Métadonnées du test
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $testMetadata = null; // langue, dates, durées, version

    // Rapport d'orientation généré
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $analysis = null;

    // Statut de complétion
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $currentStep = null; // 1-8

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isCompleted = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $startedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $completedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->isCompleted = false;
        $this->currentStep = 1;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getPersonalInfo(): ?array
    {
        return $this->personalInfo;
    }

    public function setPersonalInfo(?array $personalInfo): self
    {
        $this->personalInfo = $personalInfo;
        return $this;
    }

    public function getRiasec(): ?array
    {
        return $this->riasec;
    }

    public function setRiasec(?array $riasec): self
    {
        $this->riasec = $riasec;
        return $this;
    }

    public function getPersonality(): ?array
    {
        return $this->personality;
    }

    public function setPersonality(?array $personality): self
    {
        $this->personality = $personality;
        return $this;
    }

    public function getAptitude(): ?array
    {
        return $this->aptitude;
    }

    public function setAptitude(?array $aptitude): self
    {
        $this->aptitude = $aptitude;
        return $this;
    }

    public function getInterests(): ?array
    {
        return $this->interests;
    }

    public function setInterests(?array $interests): self
    {
        $this->interests = $interests;
        return $this;
    }

    public function getCareer(): ?array
    {
        return $this->career;
    }

    public function setCareer(?array $career): self
    {
        $this->career = $career;
        return $this;
    }

    public function getConstraints(): ?array
    {
        return $this->constraints;
    }

    public function setConstraints(?array $constraints): self
    {
        $this->constraints = $constraints;
        return $this;
    }

    public function getLanguages(): ?array
    {
        return $this->languages;
    }

    public function setLanguages(?array $languages): self
    {
        $this->languages = $languages;
        return $this;
    }

    public function getTestMetadata(): ?array
    {
        return $this->testMetadata;
    }

    public function setTestMetadata(?array $testMetadata): self
    {
        $this->testMetadata = $testMetadata;
        return $this;
    }

    public function getAnalysis(): ?array
    {
        return $this->analysis;
    }

    public function setAnalysis(?array $analysis): self
    {
        $this->analysis = $analysis;
        return $this;
    }

    public function getCurrentStep(): ?int
    {
        return $this->currentStep;
    }

    public function setCurrentStep(?int $currentStep): self
    {
        $this->currentStep = $currentStep;
        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->isCompleted;
    }

    public function setIsCompleted(bool $isCompleted): self
    {
        $this->isCompleted = $isCompleted;
        if ($isCompleted && $this->completedAt === null) {
            $this->completedAt = new \DateTime();
        }
        return $this;
    }

    public function getStartedAt(): ?\DateTimeInterface
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeInterface $startedAt): self
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeInterface $completedAt): self
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTime();
        }
        if ($this->updatedAt === null) {
            $this->updatedAt = new \DateTime();
        }
        if ($this->startedAt === null) {
            $this->startedAt = new \DateTime();
        }
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }
}
