<?php

declare(strict_types=1);

namespace AppBundle\Controller\Website\CompanyPublicProfile;

use AppBundle\Association\Entity\PersonneMorale;
use AppBundle\Association\Entity\Repository\PersonneMoraleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

abstract class CompanyPublicProfileController extends AbstractController
{
    public function __construct(private readonly PersonneMoraleRepository $personneMoraleRepository) {}

    protected function checkAndGetCompanyMember(int $id, string $slug): PersonneMorale
    {
        $companyMember = $this->personneMoraleRepository->findById($id);

        if ($companyMember === null
            || $companyMember->getSlug() != $slug
            || false === $companyMember->publicProfileEnabled
            || false === $companyMember->hasUpToDateMembershipFee()
        ) {
            throw $this->createNotFoundException("Company member not found");
        }

        return $companyMember;
    }
}
