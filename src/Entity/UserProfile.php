<?php

namespace App\Entity;

use App\Repository\UserProfileRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserProfileRepository::class)]
#[ORM\HasLifecycleCallbacks]
class UserProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'profile', targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    // Informations académiques
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $userType = null; // 'student' ou 'tutor'

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $niveau = null;

    #[ORM\Column(name: 'bac_type', length: 20, nullable: true)]
    private ?string $bacType = null; // 'normal' ou 'mission'

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filiere = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $specialite1 = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $specialite2 = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $specialite3 = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $diplomeEnCours = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nomEtablissement = null;

    /** Type de lycée : 'public' ou 'prive' */
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $typeLycee = null;

    // Préférences
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $typeEcolePrefere = null; // ['public', 'prive', 'militaire', 'semi-public']

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $servicesPrefere = null; // ['orientation', 'inscription', 'notifications']

    // Informations personnelles
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $prenom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateNaissance = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $genre = null; // 'Homme' ou 'Femme'

    #[ORM\ManyToOne]
    private ?City $ville = null;

    // Informations tuteur
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $tuteur = null; // 'Père', 'Mère', 'Autre'

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nomTuteur = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $prenomTuteur = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telTuteur = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $professionTuteur = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $adresseTuteur = null;

    // Accord
    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $consentContact = false;

    // Plan de réussite - Étapes complétées
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $planReussiteSteps = null; // ['reportStepCompleted' => true, 'step3_visited' => true, etc.]

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
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

    public function getUserType(): ?string
    {
        return $this->userType;
    }

    public function setUserType(?string $userType): self
    {
        $this->userType = $userType;
        return $this;
    }

    public function getNiveau(): ?string
    {
        return $this->niveau;
    }

    public function setNiveau(?string $niveau): self
    {
        $this->niveau = $niveau;
        return $this;
    }

    public function getBacType(): ?string
    {
        return $this->bacType;
    }

    public function setBacType(?string $bacType): self
    {
        $this->bacType = $bacType;
        return $this;
    }

    public function getFiliere(): ?string
    {
        return $this->filiere;
    }

    public function setFiliere(?string $filiere): self
    {
        $this->filiere = $filiere;
        return $this;
    }

    public function getSpecialite1(): ?string
    {
        return $this->specialite1;
    }

    public function setSpecialite1(?string $specialite1): self
    {
        $this->specialite1 = $specialite1;
        return $this;
    }

    public function getSpecialite2(): ?string
    {
        return $this->specialite2;
    }

    public function setSpecialite2(?string $specialite2): self
    {
        $this->specialite2 = $specialite2;
        return $this;
    }

    public function getSpecialite3(): ?string
    {
        return $this->specialite3;
    }

    public function setSpecialite3(?string $specialite3): self
    {
        $this->specialite3 = $specialite3;
        return $this;
    }

    public function getDiplomeEnCours(): ?string
    {
        return $this->diplomeEnCours;
    }

    public function setDiplomeEnCours(?string $diplomeEnCours): self
    {
        $this->diplomeEnCours = $diplomeEnCours;
        return $this;
    }

    public function getNomEtablissement(): ?string
    {
        return $this->nomEtablissement;
    }

    public function setNomEtablissement(?string $nomEtablissement): self
    {
        $this->nomEtablissement = $nomEtablissement;
        return $this;
    }

    public function getTypeLycee(): ?string
    {
        return $this->typeLycee;
    }

    public function setTypeLycee(?string $typeLycee): self
    {
        $this->typeLycee = $typeLycee;
        return $this;
    }

    public function getTypeEcolePrefere(): ?array
    {
        return $this->typeEcolePrefere;
    }

    public function setTypeEcolePrefere(?array $typeEcolePrefere): self
    {
        $this->typeEcolePrefere = $typeEcolePrefere;
        return $this;
    }

    public function getServicesPrefere(): ?array
    {
        return $this->servicesPrefere;
    }

    public function setServicesPrefere(?array $servicesPrefere): self
    {
        $this->servicesPrefere = $servicesPrefere;
        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(?\DateTimeInterface $dateNaissance): self
    {
        $this->dateNaissance = $dateNaissance;
        return $this;
    }

    public function getGenre(): ?string
    {
        return $this->genre;
    }

    public function setGenre(?string $genre): self
    {
        $this->genre = $genre;
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

    public function getTuteur(): ?string
    {
        return $this->tuteur;
    }

    public function setTuteur(?string $tuteur): self
    {
        $this->tuteur = $tuteur;
        return $this;
    }

    public function getNomTuteur(): ?string
    {
        return $this->nomTuteur;
    }

    public function setNomTuteur(?string $nomTuteur): self
    {
        $this->nomTuteur = $nomTuteur;
        return $this;
    }

    public function getPrenomTuteur(): ?string
    {
        return $this->prenomTuteur;
    }

    public function setPrenomTuteur(?string $prenomTuteur): self
    {
        $this->prenomTuteur = $prenomTuteur;
        return $this;
    }

    public function getTelTuteur(): ?string
    {
        return $this->telTuteur;
    }

    public function setTelTuteur(?string $telTuteur): self
    {
        $this->telTuteur = $telTuteur;
        return $this;
    }

    public function getProfessionTuteur(): ?string
    {
        return $this->professionTuteur;
    }

    public function setProfessionTuteur(?string $professionTuteur): self
    {
        $this->professionTuteur = $professionTuteur;
        return $this;
    }

    public function getAdresseTuteur(): ?string
    {
        return $this->adresseTuteur;
    }

    public function setAdresseTuteur(?string $adresseTuteur): self
    {
        $this->adresseTuteur = $adresseTuteur;
        return $this;
    }

    public function getConsentContact(): ?bool
    {
        return $this->consentContact;
    }

    public function setConsentContact(?bool $consentContact): self
    {
        $this->consentContact = $consentContact;
        return $this;
    }

    public function getPlanReussiteSteps(): ?array
    {
        return $this->planReussiteSteps;
    }

    public function setPlanReussiteSteps(?array $planReussiteSteps): self
    {
        $this->planReussiteSteps = $planReussiteSteps;
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
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }
}
