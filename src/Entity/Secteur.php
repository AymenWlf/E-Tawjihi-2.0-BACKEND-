<?php

namespace App\Entity;

use App\Repository\SecteurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SecteurRepository::class)]
#[ORM\Table(name: 'secteurs')]
#[ORM\HasLifecycleCallbacks]
class Secteur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['secteur:read', 'secteur:list'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['secteur:read', 'secteur:write', 'secteur:list'])]
    private ?string $titre = null;

    #[ORM\Column(length: 50)]
    #[Groups(['secteur:read', 'secteur:write', 'secteur:list'])]
    private ?string $code = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['secteur:read', 'secteur:write', 'secteur:list'])]
    private ?string $description = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['secteur:read', 'secteur:write', 'secteur:list'])]
    private ?string $icon = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['secteur:read', 'secteur:write', 'secteur:list'])]
    private ?string $image = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['secteur:read', 'secteur:write', 'secteur:list'])]
    private ?array $softSkills = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['secteur:read', 'secteur:write', 'secteur:list'])]
    private ?array $personnalites = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['secteur:read', 'secteur:write', 'secteur:list'])]
    private ?array $bacs = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['secteur:read', 'secteur:write', 'secteur:list'])]
    private ?array $typeBacs = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['secteur:read', 'secteur:write', 'secteur:list'])]
    private ?array $avantages = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['secteur:read', 'secteur:write', 'secteur:list'])]
    private ?array $inconvenients = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['secteur:read', 'secteur:write', 'secteur:list'])]
    private ?array $metiers = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['secteur:read', 'secteur:write', 'secteur:list'])]
    private ?array $keywords = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Groups(['secteur:read', 'secteur:write', 'secteur:list'])]
    private ?string $salaireMin = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Groups(['secteur:read', 'secteur:write', 'secteur:list'])]
    private ?string $salaireMax = null;

    #[ORM\Column]
    #[Groups(['secteur:read', 'secteur:write', 'secteur:list'])]
    private ?bool $isActivate = true;

    #[ORM\Column]
    #[Groups(['secteur:read', 'secteur:write', 'secteur:list'])]
    private ?bool $afficherDansTest = true;

    #[ORM\Column(length: 50)]
    #[Groups(['secteur:read', 'secteur:write', 'secteur:list'])]
    private ?string $status = 'Actif';

    #[ORM\Column]
    #[Groups(['secteur:read', 'secteur:write', 'secteur:list'])]
    private ?bool $isComplet = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['secteur:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['secteur:read'])]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'secteur', targetEntity: Metier::class, cascade: ['persist', 'remove'])]
    #[Groups(['secteur:read'])]
    private Collection $metiersList;

    public function __construct()
    {
        $this->metiersList = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime();
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
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

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getSoftSkills(): ?array
    {
        return $this->softSkills;
    }

    public function setSoftSkills(?array $softSkills): static
    {
        $this->softSkills = $softSkills;
        return $this;
    }

    public function getPersonnalites(): ?array
    {
        return $this->personnalites;
    }

    public function setPersonnalites(?array $personnalites): static
    {
        $this->personnalites = $personnalites;
        return $this;
    }

    public function getBacs(): ?array
    {
        return $this->bacs;
    }

    public function setBacs(?array $bacs): static
    {
        $this->bacs = $bacs;
        return $this;
    }

    public function getTypeBacs(): ?array
    {
        return $this->typeBacs;
    }

    public function setTypeBacs(?array $typeBacs): static
    {
        $this->typeBacs = $typeBacs;
        return $this;
    }

    public function getAvantages(): ?array
    {
        return $this->avantages;
    }

    public function setAvantages(?array $avantages): static
    {
        $this->avantages = $avantages;
        return $this;
    }

    public function getInconvenients(): ?array
    {
        return $this->inconvenients;
    }

    public function setInconvenients(?array $inconvenients): static
    {
        $this->inconvenients = $inconvenients;
        return $this;
    }

    public function getMetiers(): ?array
    {
        return $this->metiers;
    }

    public function setMetiers(?array $metiers): static
    {
        $this->metiers = $metiers;
        return $this;
    }

    public function getKeywords(): ?array
    {
        return $this->keywords;
    }

    public function setKeywords(?array $keywords): static
    {
        $this->keywords = $keywords;
        return $this;
    }

    public function getSalaireMin(): ?string
    {
        return $this->salaireMin;
    }

    public function setSalaireMin(?string $salaireMin): static
    {
        $this->salaireMin = $salaireMin;
        return $this;
    }

    public function getSalaireMax(): ?string
    {
        return $this->salaireMax;
    }

    public function setSalaireMax(?string $salaireMax): static
    {
        $this->salaireMax = $salaireMax;
        return $this;
    }

    public function isActivate(): ?bool
    {
        return $this->isActivate;
    }

    public function setIsActivate(bool $isActivate): static
    {
        $this->isActivate = $isActivate;
        return $this;
    }

    public function isAfficherDansTest(): ?bool
    {
        return $this->afficherDansTest;
    }

    public function setAfficherDansTest(bool $afficherDansTest): static
    {
        $this->afficherDansTest = $afficherDansTest;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function isComplet(): ?bool
    {
        return $this->isComplet;
    }

    public function setIsComplet(bool $isComplet): static
    {
        $this->isComplet = $isComplet;
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

    /**
     * @return Collection<int, Metier>
     */
    public function getMetiersList(): Collection
    {
        return $this->metiersList;
    }

    public function addMetiersList(Metier $metiersList): static
    {
        if (!$this->metiersList->contains($metiersList)) {
            $this->metiersList->add($metiersList);
            $metiersList->setSecteur($this);
        }

        return $this;
    }

    public function removeMetiersList(Metier $metiersList): static
    {
        if ($this->metiersList->removeElement($metiersList)) {
            // set the owning side to null (unless already changed)
            if ($metiersList->getSecteur() === $this) {
                $metiersList->setSecteur(null);
            }
        }

        return $this;
    }
}
