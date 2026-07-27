<?php

declare(strict_types=1);

namespace AppBundle\Controller\Website\Membership;

use AppBundle\Association\CompanyMembership\InvitationMail;
use AppBundle\Association\CompanyMembership\SubscriptionManagement;
use AppBundle\Association\Entity\PersonneMorale;
use AppBundle\Association\Entity\PersonneMoraleInvitation;
use AppBundle\Association\Entity\Repository\PersonneMoraleInvitationRepository;
use AppBundle\Association\Entity\Repository\PersonneMoraleRepository;
use AppBundle\Association\Form\CompanyMemberType;
use AppBundle\Twig\ViewRenderer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Webmozart\Assert\Assert;

final class CompanyAction extends AbstractController
{
    public function __construct(
        private readonly ViewRenderer $view,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly PersonneMoraleRepository $personneMoraleRepository,
        private readonly InvitationMail $invitationMail,
        private readonly SubscriptionManagement $subscriptionManagement,
        private readonly PersonneMoraleInvitationRepository $personneMoraleInvitationRepository,
    ) {}

    public function __invoke(Request $request): Response
    {
        $firstInvitation = new PersonneMoraleInvitation();
        $firstInvitation->manager = true;

        $data = new PersonneMorale();
        $data->invitations = [$firstInvitation];

        $subscribeForm = $this->createForm(CompanyMemberType::class, $data);
        $subscribeForm->handleRequest($request);

        if ($subscribeForm->isSubmitted() && $subscribeForm->isValid()) {
            /**
             * @var PersonneMorale $member
             */
            $member = $subscribeForm->getData();
            $this->personneMoraleRepository->save($member);
            Assert::notNull($member->id);

            $invitations = $member->invitations ?? [];
            foreach ($invitations as $index => $invitation) {
                if ($invitation->email === '') {
                    continue;
                }
                $invitation->submittedOn = new \DateTime();
                $invitation->companyId = $member->id;
                $invitation->token = base64_encode(random_bytes(30));
                $invitation->status = PersonneMoraleInvitation::STATUS_PENDING;
                if ($index === 0) {
                    // By security, force first employee to be defined as a manager
                    $invitation->manager = true;
                }

                $this->personneMoraleInvitationRepository->save($invitation);

                // Send mail to the other guy, begging for him to join the company
                $this->eventDispatcher->addListener(KernelEvents::TERMINATE, function () use ($member, $invitation): void {
                    $this->invitationMail->sendInvitation($member, $invitation);
                });
            }

            $subscriptionManager = $this->subscriptionManagement;
            $invoice = $subscriptionManager->createInvoiceForInscription($member, count($invitations));

            return $this->redirectToRoute('company_membership_payment', ['invoiceNumber' => $invoice['invoice'], 'token' => $invoice['token']]);
        }

        return $this->view->render('site/company_membership/adhesion_entreprise.html.twig', [
            'form' => $subscribeForm->createView(),
        ]);
    }
}
