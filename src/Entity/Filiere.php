<?php

namespace App\Entity;

use App\Repository\FiliereRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: FiliereRepository::class)]
#[ORM\Table(name: 'filieres')]
#[ORM\HasLifecycleCallbacks]
class Filiere
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['filiere:read', 'filiere:list'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['filiere:read', 'filiere:write', 'filiere:list'])]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['filiere:read', 'filiere:write', 'filiere:list'])]
    private ?string $nomArabe = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['filiere:read', 'filiere:write', 'filiere:list'])]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['filiere:read', 'filiere:write'])]
    private ?string $description = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['filiere:read', 'filiere:write', 'filiere:list'])]
    private ?string $imageCouverture = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['filiere:read', 'filiere:write', 'filiere:list'])]
    private ?string $diplome = null; // Master, Licence, Doctorat, etc.

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['filiere:read', 'filiere:write', 'filiere:list'])]
    private ?string $domaine = null; // Domaine d'études (Informatique, Commerce, etc.)

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['filiere:read', 'filiere:write', 'filiere:list'])]
    private ?string $langueEtudes = null; // Français, Anglais, etc.

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Groups(['filiere:read', 'filiere:write', 'filiere:list'])]
    private ?string $fraisScolarite = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Groups(['filiere:read', 'filiere:write'])]
    private ?string $fraisInscription = null;

    #[ORM\Column]
    #[Groups(['filiere:read', 'filiere:write'])]
    private ?bool $concours = false;

    #[ORM\Column(nullable: true)]
    #[Groups(['filiere:read', 'filiere:write'])]
    private ?int $nbPlaces = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['filiere:read', 'filiere:write', 'filiere:list'])]
    private ?string $nombreAnnees = null; // "2 ans", "3 ans", etc.

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['filiere:read', 'filiere:write', 'filiere:list'])]
    private ?string $typeEcole = null; // Privé, Public

    #[ORM\Column]
    #[Groups(['filiere:read', 'filiere:write', 'filiere:list'])]
    private ?bool $bacCompatible = false;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['filiere:read', 'filiere:write'])]
    private ?string $bacType = null; // 'normal', 'mission' ou 'both'

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['filiere:read', 'filiere:write'])]
    private ?array $filieresAcceptees = null; // Pour bac normal

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['filiere:read', 'filiere:write'])]
    private ?array $combinaisonsBacMission = null; // Pour bac mission: [['Mathématiques', 'Physique-Chimie'], ['SVT', 'NSI']]

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['filiere:read', 'filiere:write', 'filiere:list'])]
    private ?array $secteursIds = null; // IDs des secteurs de métiers associés

    #[ORM\Column]
    #[Groups(['filiere:read', 'filiere:write', 'filiere:list'])]
    private ?bool $recommandee = false;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['filiere:read', 'filiere:write'])]
    private ?array $metier = null; // Informations sur le métier associé

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['filiere:read', 'filiere:write'])]
    private ?array $objectifs = null; // Objectifs de la formation

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['filiere:read', 'filiere:write'])]
    private ?array $programme = null; // Programme détaillé par semestre

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['filiere:read', 'filiere:write'])]
    private ?array $documents = null; // Documents et brochures

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['filiere:read', 'filiere:write'])]
    private ?array $photos = null; // Photos de la filière

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['filiere:read', 'filiere:write'])]
    private ?string $videoUrl = null; // URL de la vidéo

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['filiere:read', 'filiere:write'])]
    private ?string $reconnaissance = null; // Reconnaissance du diplôme

    #[ORM\Column]
    #[Groups(['filiere:read', 'filiere:write'])]
    private ?bool $echangeInternational = false; // Programme d'échange international

    #[ORM\ManyToOne(targetEntity: Establishment::class, inversedBy: 'filieres')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['filiere:write'])]
    private ?Establishment $establishment = null;

    #[ORM\ManyToMany(targetEntity: Campus::class)]
    #[ORM\JoinTable(name: 'filiere_campus')]
    #[Groups(['filiere:read', 'filiere:write'])]
    private Collection $campus;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['filiere:read', 'filiere:write'])]
    private ?string $metaTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['filiere:read', 'filiere:write'])]
    private ?string $metaDescription = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['filiere:read', 'filiere:write'])]
    private ?string $metaKeywords = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['filiere:read', 'filiere:write'])]
    private ?string $ogImage = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['filiere:read', 'filiere:write'])]
    private ?string $canonicalUrl = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['filiere:read', 'filiere:write'])]
    private ?string $schemaType = 'EducationalProgram';

    #[ORM\Column]
    #[Groups(['filiere:read', 'filiere:write'])]
    private ?bool $noIndex = false;

    #[ORM\Column]
    #[Groups(['filiere:read', 'filiere:write', 'filiere:list'])]
    private ?bool $isActive = true;

    #[ORM\Column]
    #[Groups(['filiere:read', 'filiere:write', 'filiere:list'])]
    private ?bool $isSponsored = false;

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['filiere:read', 'filiere:write', 'filiere:list'])]
    private ?int $viewCount = 0; // Nombre de vues de la filière

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['filiere:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['filiere:read'])]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->campus = new ArrayCollection();
    }

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getImageCouverture(): ?string
    {
        return $this->imageCouverture;
    }

    public function setImageCouverture(?string $imageCouverture): static
    {
        $this->imageCouverture = $imageCouverture;
        return $this;
    }

    public function getDiplome(): ?string
    {
        return $this->diplome;
    }

    public function setDiplome(?string $diplome): static
    {
        $this->diplome = $diplome;
        return $this;
    }

    public function getDomaine(): ?string
    {
        return $this->domaine;
    }

    public function setDomaine(?string $domaine): static
    {
        $this->domaine = $domaine;
        return $this;
    }

    public function getLangueEtudes(): ?string
    {
        return $this->langueEtudes;
    }

    public function setLangueEtudes(?string $langueEtudes): static
    {
        $this->langueEtudes = $langueEtudes;
        return $this;
    }

    public function getFraisScolarite(): ?string
    {
        return $this->fraisScolarite;
    }

    public function setFraisScolarite(?string $fraisScolarite): static
    {
        $this->fraisScolarite = $fraisScolarite;
        return $this;
    }

    public function getFraisInscription(): ?string
    {
        return $this->fraisInscription;
    }

    public function setFraisInscription(?string $fraisInscription): static
    {
        $this->fraisInscription = $fraisInscription;
        return $this;
    }

    public function isConcours(): ?bool
    {
        return $this->concours;
    }

    public function setConcours(bool $concours): static
    {
        $this->concours = $concours;
        return $this;
    }

    public function getNbPlaces(): ?int
    {
        return $this->nbPlaces;
    }

    public function setNbPlaces(?int $nbPlaces): static
    {
        $this->nbPlaces = $nbPlaces;
        return $this;
    }

    public function getNombreAnnees(): ?string
    {
        return $this->nombreAnnees;
    }

    public function setNombreAnnees(?string $nombreAnnees): static
    {
        $this->nombreAnnees = $nombreAnnees;
        return $this;
    }

    public function getTypeEcole(): ?string
    {
        return $this->typeEcole;
    }

    public function setTypeEcole(?string $typeEcole): static
    {
        $this->typeEcole = $typeEcole;
        return $this;
    }

    public function isBacCompatible(): ?bool
    {
        return $this->bacCompatible;
    }

    public function setBacCompatible(bool $bacCompatible): static
    {
        $this->bacCompatible = $bacCompatible;
        return $this;
    }

    public function getBacType(): ?string
    {
        return $this->bacType;
    }

    public function setBacType(?string $bacType): static
    {
        $this->bacType = $bacType;
        return $this;
    }

    public function getFilieresAcceptees(): ?array
    {
        return $this->filieresAcceptees;
    }

    public function setFilieresAcceptees(?array $filieresAcceptees): static
    {
        $this->filieresAcceptees = $filieresAcceptees;
        return $this;
    }

    public function getCombinaisonsBacMission(): ?array
    {
        return $this->combinaisonsBacMission;
    }

    public function setCombinaisonsBacMission(?array $combinaisonsBacMission): static
    {
        $this->combinaisonsBacMission = $combinaisonsBacMission;
        return $this;
    }

    public function getSecteursIds(): ?array
    {
        return $this->secteursIds;
    }

    public function setSecteursIds(?array $secteursIds): static
    {
        $this->secteursIds = $secteursIds;
        return $this;
    }

    public function isRecommandee(): ?bool
    {
        return $this->recommandee;
    }

    public function setRecommandee(bool $recommandee): static
    {
        $this->recommandee = $recommandee;
        return $this;
    }

    public function getMetier(): ?array
    {
        return $this->metier;
    }

    public function setMetier(?array $metier): static
    {
        $this->metier = $metier;
        return $this;
    }

    public function getObjectifs(): ?array
    {
        return $this->objectifs;
    }

    public function setObjectifs(?array $objectifs): static
    {
        $this->objectifs = $objectifs;
        return $this;
    }

    public function getProgramme(): ?array
    {
        return $this->programme;
    }

    public function setProgramme(?array $programme): static
    {
        $this->programme = $programme;
        return $this;
    }

    public function getDocuments(): ?array
    {
        return $this->documents;
    }

    public function setDocuments(?array $documents): static
    {
        $this->documents = $documents;
        return $this;
    }

    public function getPhotos(): ?array
    {
        return $this->photos;
    }

    public function setPhotos(?array $photos): static
    {
        $this->photos = $photos;
        return $this;
    }

    public function getVideoUrl(): ?string
    {
        return $this->videoUrl;
    }

    public function setVideoUrl(?string $videoUrl): static
    {
        $this->videoUrl = $videoUrl;
        return $this;
    }

    public function getReconnaissance(): ?string
    {
        return $this->reconnaissance;
    }

    public function setReconnaissance(?string $reconnaissance): static
    {
        $this->reconnaissance = $reconnaissance;
        return $this;
    }

    public function isEchangeInternational(): ?bool
    {
        return $this->echangeInternational;
    }

    public function setEchangeInternational(bool $echangeInternational): static
    {
        $this->echangeInternational = $echangeInternational;
        return $this;
    }

    public function getEstablishment(): ?Establishment
    {
        return $this->establishment;
    }

    public function setEstablishment(?Establishment $establishment): static
    {
        $this->establishment = $establishment;
        return $this;
    }

    /**
     * @return Collection<int, Campus>
     */
    public function getCampus(): Collection
    {
        return $this->campus;
    }

    public function addCampus(Campus $campus): static
    {
        if (!$this->campus->contains($campus)) {
            $this->campus->add($campus);
        }

        return $this;
    }

    public function removeCampus(Campus $campus): static
    {
        $this->campus->removeElement($campus);

        return $this;
    }

    public function getMetaTitle(): ?string
    {
        return $this->metaTitle;
    }

    public function setMetaTitle(?string $metaTitle): static
    {
        $this->metaTitle = $metaTitle;
        return $this;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): static
    {
        $this->metaDescription = $metaDescription;
        return $this;
    }

    public function getMetaKeywords(): ?string
    {
        return $this->metaKeywords;
    }

    public function setMetaKeywords(?string $metaKeywords): static
    {
        $this->metaKeywords = $metaKeywords;
        return $this;
    }

    public function getOgImage(): ?string
    {
        return $this->ogImage;
    }

    public function setOgImage(?string $ogImage): static
    {
        $this->ogImage = $ogImage;
        return $this;
    }

    public function getCanonicalUrl(): ?string
    {
        return $this->canonicalUrl;
    }

    public function setCanonicalUrl(?string $canonicalUrl): static
    {
        $this->canonicalUrl = $canonicalUrl;
        return $this;
    }

    public function getSchemaType(): ?string
    {
        return $this->schemaType;
    }

    public function setSchemaType(?string $schemaType): static
    {
        $this->schemaType = $schemaType;
        return $this;
    }

    public function isNoIndex(): ?bool
    {
        return $this->noIndex;
    }

    public function setNoIndex(bool $noIndex): static
    {
        $this->noIndex = $noIndex;
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

    public function isSponsored(): ?bool
    {
        return $this->isSponsored;
    }

    public function setIsSponsored(bool $isSponsored): static
    {
        $this->isSponsored = $isSponsored;
        return $this;
    }

    public function getViewCount(): ?int
    {
        return $this->viewCount;
    }

    public function setViewCount(int $viewCount): static
    {
        $this->viewCount = $viewCount;
        return $this;
    }

    public function incrementViewCount(): static
    {
        $this->viewCount = ($this->viewCount ?? 0) + 1;
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
