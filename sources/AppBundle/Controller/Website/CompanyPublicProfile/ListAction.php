<?php

declare(strict_types=1);

namespace AppBundle\Controller\Website\CompanyPublicProfile;

use AppBundle\Association\Entity\PersonneMorale;
use AppBundle\Association\Entity\Repository\PersonneMoraleRepository;
use AppBundle\Twig\ViewRenderer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class ListAction extends AbstractController
{
    public function __construct(
        private readonly ViewRenderer $view,
        private readonly PersonneMoraleRepository $personneMoraleRepository,
    ) {}

    public function __invoke(): Response
    {
        $displayableCompanies = $this->personneMoraleRepository->findDisplayableCompanies();

        usort($displayableCompanies, function (PersonneMorale $companyMemberA, PersonneMorale $companyMemberB): int {
            $a = $companyMemberA->companyName;
            $b = $companyMemberB->companyName;
            return $a <=> $b;
        });

        return $this->view->render('site/company_public_profile_list.html.twig', [
            'company_member_list' => $displayableCompanies,
        ]);
    }
}
