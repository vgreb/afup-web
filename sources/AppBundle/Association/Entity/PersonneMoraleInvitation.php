<?php

declare(strict_types=1);

namespace AppBundle\Association\Entity;

use AppBundle\Association\Entity\Repository\PersonneMoraleInvitationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PersonneMoraleInvitationRepository::class)]
#[ORM\Table(name: 'afup_personnes_morales_invitations')]
class PersonneMoraleInvitation
{
    public const int STATUS_PENDING = 0;
    public const int STATUS_ACCEPTED = 1;
    public const int STATUS_CANCELLED = 2;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column]
    public int $companyId = 0;

    #[ORM\Column]
    #[Assert\Email]
    public string $email = '';

    #[ORM\Column]
    public string $token = '';

    #[ORM\Column]
    public bool $manager = false;

    #[ORM\Column(type: 'datetime')]
    public \DateTime $submittedOn;

    #[ORM\Column]
    public int $status = self::STATUS_PENDING;
}
