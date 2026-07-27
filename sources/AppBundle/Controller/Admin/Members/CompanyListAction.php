<?php

declare(strict_types=1);

namespace AppBundle\Controller\Admin\Members;

use AppBundle\Association\Entity\Repository\PersonneMoraleRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class CompanyListAction
{
    public function __construct(
        private readonly PersonneMoraleRepository $personneMoraleRepository,
        private readonly Environment $twig,
    ) {}

    public function __invoke(Request $request)
    {
        // Modification des paramètres de tri en fonction des demandes passées en GET
        $sort = $request->query->get('sort', 'name');
        $direction = $request->query->get('direction', 'asc');
        $filter = $request->query->get('filter');
        $onlyDisplayActive = !$request->query->getBoolean('alsoDisplayInactive');

        return new Response($this->twig->render('admin/members/company_list.html.twig', [
            'companies' => $this->personneMoraleRepository->search(
                $sort,
                $direction,
                $filter,
                $onlyDisplayActive,
            ),
            'activeMembers' => $this->personneMoraleRepository->countActiveByCompany(),
            'onlyDisplayActive' => $onlyDisplayActive,
            'filter' => $filter,
            'sort' => $sort,
            'direction' => $direction,
        ]));
    }
}
