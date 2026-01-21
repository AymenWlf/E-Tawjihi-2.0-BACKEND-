<?php

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ArticleRepository::class)]
#[ORM\Table(name: 'articles')]
#[ORM\HasLifecycleCallbacks]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['article:read', 'article:list'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['article:read', 'article:write', 'article:list'])]
    private ?string $titre = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['article:read', 'article:write', 'article:list'])]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['article:read', 'article:write', 'article:list'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['article:read', 'article:write'])]
    private ?string $contenu = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['article:read', 'article:write', 'article:list'])]
    private ?string $imageCouverture = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['article:read', 'article:write', 'article:list'])]
    private ?string $categorie = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['article:read', 'article:write'])]
    private ?array $categories = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['article:read', 'article:write', 'article:list'])]
    private ?array $tags = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['article:read', 'article:write', 'article:list'])]
    private ?string $auteur = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['article:read', 'article:write', 'article:list'])]
    private ?\DateTimeInterface $datePublication = null;

    #[ORM\Column(length: 50)]
    #[Groups(['article:read', 'article:write', 'article:list'])]
    private ?string $status = 'Brouillon';

    #[ORM\Column]
    #[Groups(['article:read', 'article:write', 'article:list'])]
    private ?bool $featured = false;

    // SEO
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['article:read', 'article:write'])]
    private ?string $metaTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['article:read', 'article:write'])]
    private ?string $metaDescription = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['article:read', 'article:write'])]
    private ?string $metaKeywords = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['article:read', 'article:write'])]
    private ?string $ogImage = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['article:read', 'article:write'])]
    private ?string $ogTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['article:read', 'article:write'])]
    private ?string $ogDescription = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['article:read', 'article:write'])]
    private ?string $canonicalUrl = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['article:read', 'article:write'])]
    private ?string $schemaType = null;

    #[ORM\Column]
    #[Groups(['article:read', 'article:write'])]
    private ?bool $noIndex = false;

    // Statistiques
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['article:read', 'article:write', 'article:list'])]
    private ?int $tempsLecture = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['article:read', 'article:write', 'article:list'])]
    private ?int $vues = 0;

    #[ORM\Column]
    #[Groups(['article:read', 'article:write', 'article:list'])]
    private ?bool $isActivate = true;

    #[ORM\Column]
    #[Groups(['article:read', 'article:write', 'article:list'])]
    private ?bool $isComplet = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['article:read', 'article:list'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['article:read', 'article:list'])]
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

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(?string $contenu): self
    {
        $this->contenu = $contenu;
        return $this;
    }

    public function getImageCouverture(): ?string
    {
        return $this->imageCouverture;
    }

    public function setImageCouverture(?string $imageCouverture): self
    {
        $this->imageCouverture = $imageCouverture;
        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(?string $categorie): self
    {
        $this->categorie = $categorie;
        return $this;
    }

    public function getCategories(): ?array
    {
        return $this->categories;
    }

    public function setCategories(?array $categories): self
    {
        $this->categories = $categories;
        return $this;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function setTags(?array $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    public function getAuteur(): ?string
    {
        return $this->auteur;
    }

    public function setAuteur(?string $auteur): self
    {
        $this->auteur = $auteur;
        return $this;
    }

    public function getDatePublication(): ?\DateTimeInterface
    {
        return $this->datePublication;
    }

    public function setDatePublication(?\DateTimeInterface $datePublication): self
    {
        $this->datePublication = $datePublication;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function isFeatured(): ?bool
    {
        return $this->featured;
    }

    public function setFeatured(bool $featured): self
    {
        $this->featured = $featured;
        return $this;
    }

    public function getMetaTitle(): ?string
    {
        return $this->metaTitle;
    }

    public function setMetaTitle(?string $metaTitle): self
    {
        $this->metaTitle = $metaTitle;
        return $this;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): self
    {
        $this->metaDescription = $metaDescription;
        return $this;
    }

    public function getMetaKeywords(): ?string
    {
        return $this->metaKeywords;
    }

    public function setMetaKeywords(?string $metaKeywords): self
    {
        $this->metaKeywords = $metaKeywords;
        return $this;
    }

    public function getOgImage(): ?string
    {
        return $this->ogImage;
    }

    public function setOgImage(?string $ogImage): self
    {
        $this->ogImage = $ogImage;
        return $this;
    }

    public function getOgTitle(): ?string
    {
        return $this->ogTitle;
    }

    public function setOgTitle(?string $ogTitle): self
    {
        $this->ogTitle = $ogTitle;
        return $this;
    }

    public function getOgDescription(): ?string
    {
        return $this->ogDescription;
    }

    public function setOgDescription(?string $ogDescription): self
    {
        $this->ogDescription = $ogDescription;
        return $this;
    }

    public function getCanonicalUrl(): ?string
    {
        return $this->canonicalUrl;
    }

    public function setCanonicalUrl(?string $canonicalUrl): self
    {
        $this->canonicalUrl = $canonicalUrl;
        return $this;
    }

    public function getSchemaType(): ?string
    {
        return $this->schemaType;
    }

    public function setSchemaType(?string $schemaType): self
    {
        $this->schemaType = $schemaType;
        return $this;
    }

    public function isNoIndex(): ?bool
    {
        return $this->noIndex;
    }

    public function setNoIndex(bool $noIndex): self
    {
        $this->noIndex = $noIndex;
        return $this;
    }

    public function getTempsLecture(): ?int
    {
        return $this->tempsLecture;
    }

    public function setTempsLecture(?int $tempsLecture): self
    {
        $this->tempsLecture = $tempsLecture;
        return $this;
    }

    public function getVues(): ?int
    {
        return $this->vues;
    }

    public function setVues(int $vues): self
    {
        $this->vues = $vues;
        return $this;
    }

    public function isIsActivate(): ?bool
    {
        return $this->isActivate;
    }

    public function setIsActivate(bool $isActivate): self
    {
        $this->isActivate = $isActivate;
        return $this;
    }

    public function isIsComplet(): ?bool
    {
        return $this->isComplet;
    }

    public function setIsComplet(bool $isComplet): self
    {
        $this->isComplet = $isComplet;
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
}
