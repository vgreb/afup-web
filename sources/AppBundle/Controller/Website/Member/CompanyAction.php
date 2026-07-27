<?php

declare(strict_types=1);

namespace AppBundle\Controller\Website\Member;

use AppBundle\Association\Entity\PersonneMorale;
use AppBundle\Association\Entity\Repository\PersonneMoraleRepository;
use AppBundle\Association\Form\AdminCompanyMemberType;
use AppBundle\Security\Authentication;
use AppBundle\Twig\ViewRenderer;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CompanyAction extends AbstractController
{
    public function __construct(
        private readonly PersonneMoraleRepository $personneMoraleRepository,
        private readonly ViewRenderer $view,
        private readonly Authentication $authentication,
    ) {}

    public function __invoke(Request $request): Response
    {
        $company = $this->personneMoraleRepository->find($this->authentication->getAfupUser()->getCompanyId());
        if ($company === null) {
            throw $this->createNotFoundException('Company not found');
        }

        $subscribeForm = $this->createForm(AdminCompanyMemberType::class, $company);
        $subscribeForm->handleRequest($request);

        if ($subscribeForm->isSubmitted() && $subscribeForm->isValid()) {
            /** @var PersonneMorale $member */
            $member = $subscribeForm->getData();
            try {
                $this->personneMoraleRepository->save($member);
                $this->addFlash('notice', 'Les modifications ont bien été enregistrées.');
            } catch (Exception) {
                $this->addFlash('error', 'Une erreur est survenue. Merci de nous contacter.');
            }

            return $this->redirectToRoute('member_company');
        }

        return $this->view->render('admin/association/membership/company.html.twig', [
            'title' => 'Mon adhésion entreprise',
            'form' => $subscribeForm->createView(),
        ]);
    }
}
