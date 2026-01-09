<?php

namespace App\Entity;

use App\Repository\EstablishmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: EstablishmentRepository::class)]
#[ORM\Table(name: 'establishments')]
#[ORM\HasLifecycleCallbacks]
class Establishment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['establishment:read', 'establishment:list'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['establishment:read', 'establishment:write', 'establishment:list'])]
    private ?string $nom = null;

    #[ORM\Column(length: 50)]
    #[Groups(['establishment:read', 'establishment:write', 'establishment:list'])]
    private ?string $sigle = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write', 'establishment:list'])]
    private ?string $nomArabe = null;

    #[ORM\Column(length: 50)]
    #[Groups(['establishment:read', 'establishment:write', 'establishment:list'])]
    private ?string $type = null;

    #[ORM\Column(length: 100)]
    #[Groups(['establishment:read', 'establishment:write', 'establishment:list'])]
    private ?string $ville = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write', 'establishment:list'])]
    private ?array $villes = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write', 'establishment:list'])]
    private ?string $pays = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write', 'establishment:list'])]
    private ?string $universite = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?string $description = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write', 'establishment:list'])]
    private ?string $logo = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?string $imageCouverture = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?string $email = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?string $telephone = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?string $siteWeb = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?string $adresse = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?string $codePostal = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?string $facebook = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?string $instagram = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?string $twitter = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?string $linkedin = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?string $youtube = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['establishment:read', 'establishment:write', 'establishment:list'])]
    private ?int $nbEtudiants = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['establishment:read', 'establishment:write', 'establishment:list'])]
    private ?int $nbFilieres = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?int $anneeCreation = null;

    #[ORM\Column]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?bool $accreditationEtat = false;

    #[ORM\Column]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?bool $concours = false;

    #[ORM\Column]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?bool $echangeInternational = false;

    #[ORM\Column(nullable: true)]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?int $anneesEtudes = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['establishment:read', 'establishment:write', 'establishment:list'])]
    private ?int $dureeEtudesMin = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['establishment:read', 'establishment:write', 'establishment:list'])]
    private ?int $dureeEtudesMax = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write', 'establishment:list'])]
    private ?string $fraisScolariteMin = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write', 'establishment:list'])]
    private ?string $fraisScolariteMax = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write', 'establishment:list'])]
    private ?string $fraisInscriptionMin = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write', 'establishment:list'])]
    private ?string $fraisInscriptionMax = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write', 'establishment:list'])]
    private ?array $diplomesDelivres = null;

    #[ORM\Column]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?bool $bacObligatoire = false;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['establishment:read', 'establishment:write', 'establishment:list'])]
    private ?string $slug = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?string $metaTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?string $metaDescription = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?string $metaKeywords = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?string $ogImage = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?string $canonicalUrl = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?string $schemaType = 'EducationalOrganization';

    #[ORM\Column]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?bool $noIndex = false;

    #[ORM\Column]
    #[Groups(['establishment:read', 'establishment:write', 'establishment:list'])]
    private ?bool $isActive = true;

    #[ORM\Column]
    #[Groups(['establishment:read', 'establishment:write', 'establishment:list'])]
    private ?bool $isRecommended = false;

    #[ORM\Column]
    #[Groups(['establishment:read', 'establishment:write', 'establishment:list'])]
    private ?bool $isSponsored = false;

    #[ORM\Column]
    #[Groups(['establishment:read', 'establishment:write', 'establishment:list'])]
    private ?bool $isFeatured = false;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?string $videoUrl = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?array $documents = null;

    #[ORM\OneToMany(targetEntity: Campus::class, mappedBy: 'establishment', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['establishment:read', 'establishment:write'])]
    private Collection $campus;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?array $photos = null;

    #[ORM\OneToMany(targetEntity: Filiere::class, mappedBy: 'establishment', cascade: ['persist', 'remove'])]
    #[Groups(['establishment:read'])]
    private Collection $filieres;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write', 'establishment:list'])]
    private ?string $status = 'Brouillon';

    #[ORM\Column]
    #[Groups(['establishment:read', 'establishment:write', 'establishment:list'])]
    private ?bool $isComplet = false;

    #[ORM\Column]
    #[Groups(['establishment:read', 'establishment:write', 'establishment:list'])]
    private ?bool $hasDetailPage = false;

    #[ORM\Column]
    #[Groups(['establishment:read', 'establishment:write', 'establishment:list'])]
    private ?bool $eTawjihiInscription = false;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write', 'establishment:list'])]
    private ?string $bacType = null; // 'normal', 'mission' ou 'both'

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write', 'establishment:list'])]
    private ?array $filieresAcceptees = null; // Pour bac normal

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write', 'establishment:list'])]
    private ?array $combinaisonsBacMission = null; // Pour bac mission: [['Mathématiques', 'Physique-Chimie'], ['SVT', 'NSI']]

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['establishment:read', 'establishment:list'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['establishment:read', 'establishment:list'])]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->campus = new ArrayCollection();
        $this->filieres = new ArrayCollection();
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

    public function setSigle(string $sigle): static
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(string $ville): static
    {
        $this->ville = $ville;
        return $this;
    }

    public function getVilles(): ?array
    {
        return $this->villes;
    }

    public function setVilles(?array $villes): static
    {
        $this->villes = $villes;
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

    public function getUniversite(): ?string
    {
        return $this->universite;
    }

    public function setUniversite(?string $universite): static
    {
        $this->universite = $universite;
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

    public function getImageCouverture(): ?string
    {
        return $this->imageCouverture;
    }

    public function setImageCouverture(?string $imageCouverture): static
    {
        $this->imageCouverture = $imageCouverture;
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

    public function getSiteWeb(): ?string
    {
        return $this->siteWeb;
    }

    public function setSiteWeb(?string $siteWeb): static
    {
        $this->siteWeb = $siteWeb;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getCodePostal(): ?string
    {
        return $this->codePostal;
    }

    public function setCodePostal(?string $codePostal): static
    {
        $this->codePostal = $codePostal;
        return $this;
    }

    public function getFacebook(): ?string
    {
        return $this->facebook;
    }

    public function setFacebook(?string $facebook): static
    {
        $this->facebook = $facebook;
        return $this;
    }

    public function getInstagram(): ?string
    {
        return $this->instagram;
    }

    public function setInstagram(?string $instagram): static
    {
        $this->instagram = $instagram;
        return $this;
    }

    public function getTwitter(): ?string
    {
        return $this->twitter;
    }

    public function setTwitter(?string $twitter): static
    {
        $this->twitter = $twitter;
        return $this;
    }

    public function getLinkedin(): ?string
    {
        return $this->linkedin;
    }

    public function setLinkedin(?string $linkedin): static
    {
        $this->linkedin = $linkedin;
        return $this;
    }

    public function getYoutube(): ?string
    {
        return $this->youtube;
    }

    public function setYoutube(?string $youtube): static
    {
        $this->youtube = $youtube;
        return $this;
    }

    public function getNbEtudiants(): ?int
    {
        return $this->nbEtudiants;
    }

    public function setNbEtudiants(?int $nbEtudiants): static
    {
        $this->nbEtudiants = $nbEtudiants;
        return $this;
    }

    public function getNbFilieres(): ?int
    {
        return $this->nbFilieres;
    }

    public function setNbFilieres(?int $nbFilieres): static
    {
        $this->nbFilieres = $nbFilieres;
        return $this;
    }

    public function getAnneeCreation(): ?int
    {
        return $this->anneeCreation;
    }

    public function setAnneeCreation(?int $anneeCreation): static
    {
        $this->anneeCreation = $anneeCreation;
        return $this;
    }

    public function isAccreditationEtat(): ?bool
    {
        return $this->accreditationEtat;
    }

    public function setAccreditationEtat(bool $accreditationEtat): static
    {
        $this->accreditationEtat = $accreditationEtat;
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

    public function isEchangeInternational(): ?bool
    {
        return $this->echangeInternational;
    }

    public function setEchangeInternational(bool $echangeInternational): static
    {
        $this->echangeInternational = $echangeInternational;
        return $this;
    }

    public function getAnneesEtudes(): ?int
    {
        return $this->anneesEtudes;
    }

    public function setAnneesEtudes(?int $anneesEtudes): static
    {
        $this->anneesEtudes = $anneesEtudes;
        return $this;
    }

    public function getDureeEtudesMin(): ?int
    {
        return $this->dureeEtudesMin;
    }

    public function setDureeEtudesMin(?int $dureeEtudesMin): static
    {
        $this->dureeEtudesMin = $dureeEtudesMin;
        return $this;
    }

    public function getDureeEtudesMax(): ?int
    {
        return $this->dureeEtudesMax;
    }

    public function setDureeEtudesMax(?int $dureeEtudesMax): static
    {
        $this->dureeEtudesMax = $dureeEtudesMax;
        return $this;
    }

    public function getFraisScolariteMin(): ?string
    {
        return $this->fraisScolariteMin;
    }

    public function setFraisScolariteMin(?string $fraisScolariteMin): static
    {
        $this->fraisScolariteMin = $fraisScolariteMin;
        return $this;
    }

    public function getFraisScolariteMax(): ?string
    {
        return $this->fraisScolariteMax;
    }

    public function setFraisScolariteMax(?string $fraisScolariteMax): static
    {
        $this->fraisScolariteMax = $fraisScolariteMax;
        return $this;
    }

    public function getFraisInscriptionMin(): ?string
    {
        return $this->fraisInscriptionMin;
    }

    public function setFraisInscriptionMin(?string $fraisInscriptionMin): static
    {
        $this->fraisInscriptionMin = $fraisInscriptionMin;
        return $this;
    }

    public function getFraisInscriptionMax(): ?string
    {
        return $this->fraisInscriptionMax;
    }

    public function setFraisInscriptionMax(?string $fraisInscriptionMax): static
    {
        $this->fraisInscriptionMax = $fraisInscriptionMax;
        return $this;
    }

    public function getDiplomesDelivres(): ?array
    {
        return $this->diplomesDelivres;
    }

    public function setDiplomesDelivres(?array $diplomesDelivres): static
    {
        $this->diplomesDelivres = $diplomesDelivres;
        return $this;
    }

    public function isBacObligatoire(): ?bool
    {
        return $this->bacObligatoire;
    }

    public function setBacObligatoire(bool $bacObligatoire): static
    {
        $this->bacObligatoire = $bacObligatoire;
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

    public function isRecommended(): ?bool
    {
        return $this->isRecommended;
    }

    public function setIsRecommended(bool $isRecommended): static
    {
        $this->isRecommended = $isRecommended;
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

    public function isFeatured(): ?bool
    {
        return $this->isFeatured;
    }

    public function setIsFeatured(bool $isFeatured): static
    {
        $this->isFeatured = $isFeatured;
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

    public function getDocuments(): ?array
    {
        return $this->documents;
    }

    public function setDocuments(?array $documents): static
    {
        $this->documents = $documents;
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
            $campus->setEstablishment($this);
        }

        return $this;
    }

    public function removeCampus(Campus $campus): static
    {
        if ($this->campus->removeElement($campus)) {
            // set the owning side to null (unless already changed)
            if ($campus->getEstablishment() === $this) {
                $campus->setEstablishment(null);
            }
        }

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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
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

    public function isHasDetailPage(): ?bool
    {
        return $this->hasDetailPage;
    }

    public function setHasDetailPage(bool $hasDetailPage): static
    {
        $this->hasDetailPage = $hasDetailPage;
        return $this;
    }

    public function isETawjihiInscription(): ?bool
    {
        return $this->eTawjihiInscription;
    }

    public function setETawjihiInscription(bool $eTawjihiInscription): static
    {
        $this->eTawjihiInscription = $eTawjihiInscription;
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
     * @return Collection<int, Filiere>
     */
    public function getFilieres(): Collection
    {
        return $this->filieres;
    }

    public function addFiliere(Filiere $filiere): static
    {
        if (!$this->filieres->contains($filiere)) {
            $this->filieres->add($filiere);
            $filiere->setEstablishment($this);
        }

        return $this;
    }

    public function removeFiliere(Filiere $filiere): static
    {
        if ($this->filieres->removeElement($filiere)) {
            // set the owning side to null (unless already changed)
            if ($filiere->getEstablishment() === $this) {
                $filiere->setEstablishment(null);
            }
        }

        return $this;
    }
}
