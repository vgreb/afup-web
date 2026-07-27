<?php

declare(strict_types=1);

namespace AppBundle\Association\Entity;

use AppBundle\Association\CompanyMembership\SubscriptionManagement;
use AppBundle\Association\Entity\Repository\PersonneMoraleRepository;
use Cocur\Slugify\Slugify;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PersonneMoraleRepository::class)]
#[ORM\Table(name: 'afup_personnes_morales')]
class PersonneMorale
{
    public const int STATUS_PENDING = -1;
    public const int STATUS_ACTIVE = 1;
    public const int STATUS_INACTIVE = 0;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(name: 'prenom')]
    #[Assert\NotBlank]
    public string $firstName = '';

    #[ORM\Column(name: 'nom')]
    #[Assert\NotBlank]
    public string $lastName = '';

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email = '';

    #[ORM\Column(name: 'raison_sociale')]
    #[Assert\NotBlank(message: 'Raison sociale manquante')]
    public string $companyName = '';

    #[ORM\Column]
    #[Assert\NotBlank]
    public string $siret = '';

    #[ORM\Column(name: 'adresse')]
    #[Assert\NotBlank]
    public string $address = '';

    #[ORM\Column(name: 'code_postal')]
    #[Assert\NotBlank]
    public string $zipCode = '';

    #[ORM\Column(name: 'ville')]
    #[Assert\NotBlank]
    public string $city = '';

    #[ORM\Column(name: 'id_pays')]
    #[Assert\NotBlank]
    public string $country = 'FR';

    #[ORM\Column(name: 'telephone_fixe', nullable: true)]
    public ?string $phone = null;

    #[ORM\Column(name: 'telephone_portable', nullable: true)]
    public ?string $cellphone = null;

    #[ORM\Column(name: 'etat')]
    public int $status = self::STATUS_ACTIVE;

    #[ORM\Column]
    public int $maxMembers = 0;

    #[ORM\Column]
    public bool $publicProfileEnabled = false;

    #[ORM\Column(nullable: true)]
    public ?string $description = null;

    #[ORM\Column(nullable: true)]
    public ?string $logoUrl = null;

    #[ORM\Column(nullable: true)]
    public ?string $websiteUrl = null;

    #[ORM\Column(nullable: true)]
    public ?string $contactPageUrl = null;

    #[ORM\Column(nullable: true)]
    public ?string $careersPageUrl = null;

    #[ORM\Column(nullable: true)]
    public ?string $twitterHandle = null;

    #[ORM\Column(nullable: true)]
    public ?string $relatedAfupOffices = null;

    #[ORM\Column(nullable: true)]
    public ?string $membershipReason = null;

    /**
     * Non persiste - rempli en memoire pendant le formulaire d'adhesion entreprise.
     *
     * @var PersonneMoraleInvitation[]|null
     */
    public ?array $invitations = null;

    /**
     * Non persiste - derive du MAX(date_fin) des cotisations, rempli manuellement par le repository.
     */
    public ?\DateTimeImmutable $lastSubscription = null;

    public function getSlug(): string
    {
        $slugify = new Slugify();

        return $slugify->slugify($this->companyName);
    }

    public function getCleanedTwitterHandle(): ?string
    {
        $twitter = (string) $this->twitterHandle;
        $twitter = trim($twitter, '@');
        $twitter = preg_replace('!^https?://twitter.com/!', '', $twitter);

        if (!is_string($twitter)) {
            return null;
        }

        if (trim($twitter) === '') {
            return null;
        }

        return $twitter;
    }

    public function hasLogoUrl(): bool
    {
        return null !== $this->logoUrl;
    }

    /**
     * @return string[]
     */
    public function getFormattedRelatedAfupOffices(): array
    {
        if (null === $this->relatedAfupOffices) {
            return [];
        }

        return explode(',', $this->relatedAfupOffices);
    }

    /**
     * @param string[] $relatedAfupOffices
     */
    public function setFormattedRelatedAfupOffices(array $relatedAfupOffices): void
    {
        if ($relatedAfupOffices !== []) {
            sort($relatedAfupOffices);
            $this->relatedAfupOffices = implode(',', $relatedAfupOffices);
        } else {
            $this->relatedAfupOffices = null;
        }
    }

    public function hasUpToDateMembershipFee(?\DateTimeInterface $now = null): bool
    {
        if (!$now instanceof \DateTimeInterface) {
            $now = new \DateTime();
        }

        return $this->lastSubscription > $now;
    }

    public function getMembershipFee(int $default = SubscriptionManagement::AFUP_PERSONNE_MORALE_SEUIL): float
    {
        $max = max($this->maxMembers, $default);

        return ceil($max / SubscriptionManagement::AFUP_PERSONNE_MORALE_SEUIL) * SubscriptionManagement::AFUP_COTISATION_PERSONNE_MORALE;
    }
}
