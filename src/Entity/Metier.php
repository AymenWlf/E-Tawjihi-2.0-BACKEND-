<?php

namespace App\Entity;

use App\Repository\MetierRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: MetierRepository::class)]
#[ORM\Table(name: 'metiers')]
#[ORM\HasLifecycleCallbacks]
class Metier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['metier:read', 'metier:list'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['metier:read', 'metier:write', 'metier:list'])]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['metier:read', 'metier:write', 'metier:list'])]
    private ?string $nomArabe = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['metier:read', 'metier:write', 'metier:list'])]
    private ?string $slug = null;

    #[ORM\ManyToOne(targetEntity: Secteur::class, inversedBy: 'metiersList')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['metier:read', 'metier:write', 'metier:list'])]
    private ?Secteur $secteur = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['metier:read', 'metier:write'])]
    private ?string $description = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['metier:read', 'metier:write', 'metier:list'])]
    private ?string $niveauAccessibilite = null; // Facile, Moyenne, Difficile, Très difficile, Variable

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Groups(['metier:read', 'metier:write', 'metier:list'])]
    private ?string $salaireMin = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Groups(['metier:read', 'metier:write', 'metier:list'])]
    private ?string $salaireMax = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['metier:read', 'metier:write'])]
    private ?array $competences = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['metier:read', 'metier:write'])]
    private ?array $formations = null;

    #[ORM\Column]
    #[Groups(['metier:read', 'metier:write', 'metier:list'])]
    private ?bool $isActivate = true;

    #[ORM\Column]
    #[Groups(['metier:read', 'metier:write', 'metier:list'])]
    private ?bool $afficherDansTest = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['metier:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['metier:read'])]
    private ?\DateTimeInterface $updatedAt = null;

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

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getSecteur(): ?Secteur
    {
        return $this->secteur;
    }

    public function setSecteur(?Secteur $secteur): static
    {
        $this->secteur = $secteur;
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

    public function getNiveauAccessibilite(): ?string
    {
        return $this->niveauAccessibilite;
    }

    public function setNiveauAccessibilite(?string $niveauAccessibilite): static
    {
        $this->niveauAccessibilite = $niveauAccessibilite;
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

    public function getCompetences(): ?array
    {
        return $this->competences;
    }

    public function setCompetences(?array $competences): static
    {
        $this->competences = $competences;
        return $this;
    }

    public function getFormations(): ?array
    {
        return $this->formations;
    }

    public function setFormations(?array $formations): static
    {
        $this->formations = $formations;
        return $this;
    }

    public function isIsActivate(): ?bool
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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }
}
