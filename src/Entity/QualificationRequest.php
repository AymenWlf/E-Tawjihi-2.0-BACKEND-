<?php

namespace App\Entity;

use App\Repository\QualificationRequestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QualificationRequestRepository::class)]
#[ORM\Table(name: 'qualification_requests')]
#[ORM\HasLifecycleCallbacks]
class QualificationRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $source = null; // 'landing_page', 'establishment_detail', 'filiere_detail', 'listing_promotion'

    #[ORM\ManyToOne(targetEntity: Establishment::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Establishment $establishment = null;

    #[ORM\ManyToOne(targetEntity: Filiere::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Filiere $filiere = null;

    #[ORM\Column(length: 20)]
    private ?string $tuteurEleve = null; // 'tuteur' ou 'eleve'

    #[ORM\Column(length: 255)]
    private ?string $nomPrenom = null;

    #[ORM\Column(length: 20)]
    private ?string $telephone = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $typeEcole = null;

    #[ORM\ManyToOne(targetEntity: City::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?City $ville = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $niveauEtude = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filiereBac = null;

    #[ORM\Column(length: 10)]
    private ?string $pretPayer = null; // 'oui' ou 'non'

    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $besoinOrientation = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $besoinTest = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $besoinNotification = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $besoinInscription = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column]
    private ?bool $isProcessed = false;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->isProcessed = false;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(string $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getEstablishment(): ?Establishment
    {
        return $this->establishment;
    }

    public function setEstablishment(?Establishment $establishment): self
    {
        $this->establishment = $establishment;
        return $this;
    }

    public function getFiliere(): ?Filiere
    {
        return $this->filiere;
    }

    public function setFiliere(?Filiere $filiere): self
    {
        $this->filiere = $filiere;
        return $this;
    }

    public function getTuteurEleve(): ?string
    {
        return $this->tuteurEleve;
    }

    public function setTuteurEleve(string $tuteurEleve): self
    {
        $this->tuteurEleve = $tuteurEleve;
        return $this;
    }

    public function getNomPrenom(): ?string
    {
        return $this->nomPrenom;
    }

    public function setNomPrenom(string $nomPrenom): self
    {
        $this->nomPrenom = $nomPrenom;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): self
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getTypeEcole(): ?string
    {
        return $this->typeEcole;
    }

    public function setTypeEcole(?string $typeEcole): self
    {
        $this->typeEcole = $typeEcole;
        return $this;
    }

    public function getVille(): ?City
    {
        return $this->ville;
    }

    public function setVille(?City $ville): self
    {
        $this->ville = $ville;
        return $this;
    }

    public function getNiveauEtude(): ?string
    {
        return $this->niveauEtude;
    }

    public function setNiveauEtude(?string $niveauEtude): self
    {
        $this->niveauEtude = $niveauEtude;
        return $this;
    }

    public function getFiliereBac(): ?string
    {
        return $this->filiereBac;
    }

    public function setFiliereBac(?string $filiereBac): self
    {
        $this->filiereBac = $filiereBac;
        return $this;
    }

    public function getPretPayer(): ?string
    {
        return $this->pretPayer;
    }

    public function setPretPayer(string $pretPayer): self
    {
        $this->pretPayer = $pretPayer;
        return $this;
    }

    public function getBesoinOrientation(): ?bool
    {
        return $this->besoinOrientation;
    }

    public function setBesoinOrientation(bool $besoinOrientation): self
    {
        $this->besoinOrientation = $besoinOrientation;
        return $this;
    }

    public function getBesoinTest(): ?bool
    {
        return $this->besoinTest;
    }

    public function setBesoinTest(bool $besoinTest): self
    {
        $this->besoinTest = $besoinTest;
        return $this;
    }

    public function getBesoinNotification(): ?bool
    {
        return $this->besoinNotification;
    }

    public function setBesoinNotification(bool $besoinNotification): self
    {
        $this->besoinNotification = $besoinNotification;
        return $this;
    }

    public function getBesoinInscription(): ?bool
    {
        return $this->besoinInscription;
    }

    public function setBesoinInscription(bool $besoinInscription): self
    {
        $this->besoinInscription = $besoinInscription;
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

    public function getIsProcessed(): ?bool
    {
        return $this->isProcessed;
    }

    public function setIsProcessed(bool $isProcessed): self
    {
        $this->isProcessed = $isProcessed;
        return $this;
    }
}
