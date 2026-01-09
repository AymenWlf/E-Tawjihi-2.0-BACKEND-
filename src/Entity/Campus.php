<?php

namespace App\Entity;

use App\Repository\CampusRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CampusRepository::class)]
#[ORM\Table(name: 'campus')]
class Campus
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?string $nom = null;

    #[ORM\ManyToOne(targetEntity: City::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?City $city = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?string $quartier = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?string $adresse = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?string $codePostal = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?string $telephone = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?string $email = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?string $mapUrl = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['establishment:read', 'establishment:write'])]
    private ?int $ordre = 0;

    #[ORM\ManyToOne(inversedBy: 'campus')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Establishment $establishment = null;

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

    public function getCity(): ?City
    {
        return $this->city;
    }

    public function setCity(?City $city): static
    {
        $this->city = $city;
        return $this;
    }

    /**
     * Getter de compatibilité pour retourner le nom de la ville
     * @Groups({'establishment:read', 'establishment:write'})
     */
    public function getVille(): ?string
    {
        return $this->city?->getTitre();
    }

    public function getQuartier(): ?string
    {
        return $this->quartier;
    }

    public function setQuartier(?string $quartier): static
    {
        $this->quartier = $quartier;
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

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;
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

    public function getMapUrl(): ?string
    {
        return $this->mapUrl;
    }

    public function setMapUrl(?string $mapUrl): static
    {
        $this->mapUrl = $mapUrl;
        return $this;
    }

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(?int $ordre): static
    {
        $this->ordre = $ordre;
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
}
