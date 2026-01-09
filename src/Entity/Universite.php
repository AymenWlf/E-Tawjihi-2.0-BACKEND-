<?php

namespace App\Entity;

use App\Repository\UniversiteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UniversiteRepository::class)]
#[ORM\Table(name: 'universites')]
#[ORM\HasLifecycleCallbacks]
class Universite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['universite:read', 'universite:list'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['universite:read', 'universite:write', 'universite:list'])]
    private ?string $nom = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['universite:read', 'universite:write', 'universite:list'])]
    private ?string $sigle = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['universite:read', 'universite:write'])]
    private ?string $nomArabe = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['universite:read', 'universite:write', 'universite:list'])]
    private ?string $ville = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['universite:read', 'universite:write', 'universite:list'])]
    private ?string $region = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['universite:read', 'universite:write', 'universite:list'])]
    private ?string $pays = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['universite:read', 'universite:write', 'universite:list'])]
    private ?string $type = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['universite:read', 'universite:write'])]
    private ?string $description = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['universite:read', 'universite:write', 'universite:list'])]
    private ?string $logo = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['universite:read', 'universite:write'])]
    private ?string $siteWeb = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['universite:read', 'universite:write'])]
    private ?string $email = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['universite:read', 'universite:write'])]
    private ?string $telephone = null;

    #[ORM\Column]
    #[Groups(['universite:read', 'universite:write', 'universite:list'])]
    private ?bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['universite:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['universite:read'])]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
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

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getSigle(): ?string
    {
        return $this->sigle;
    }

    public function setSigle(?string $sigle): static
    {
        $this->sigle = $sigle;
        return $this;
    }

    public function getNomArabe(): ?string
    {
        return $this->nomArabe;
    }

    public function setNomArabe(?string $nomArabe): static
    {
        $this->nomArabe = $nomArabe;
        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(?string $ville): static
    {
        $this->ville = $ville;
        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): static
    {
        $this->region = $region;
        return $this;
    }

    public function getPays(): ?string
    {
        return $this->pays;
    }

    public function setPays(?string $pays): static
    {
        $this->pays = $pays;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;
        return $this;
    }

    public function getSiteWeb(): ?string
    {
        return $this->siteWeb;
    }

    public function setSiteWeb(?string $siteWeb): static
    {
        $this->siteWeb = $siteWeb;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
