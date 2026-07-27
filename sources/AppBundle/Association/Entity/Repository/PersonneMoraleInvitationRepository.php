<?php

declare(strict_types=1);

namespace AppBundle\Association\Entity\Repository;

use AppBundle\Association\Entity\PersonneMoraleInvitation;
use AppBundle\Doctrine\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends EntityRepository<PersonneMoraleInvitation>
 */
class PersonneMoraleInvitationRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PersonneMoraleInvitation::class);
    }

    /**
     * @return PersonneMoraleInvitation[]
     */
    public function loadPendingInvitationsByCompany(int $companyId): array
    {
        return $this->findBy(['companyId' => $companyId, 'status' => PersonneMoraleInvitation::STATUS_PENDING]);
    }
}
