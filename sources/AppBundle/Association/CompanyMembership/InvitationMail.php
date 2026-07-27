<?php

declare(strict_types=1);

namespace AppBundle\Association\CompanyMembership;

use AppBundle\Association\Entity\PersonneMorale;
use AppBundle\Association\Entity\PersonneMoraleInvitation;
use AppBundle\Email\Mailer\Mailer;
use AppBundle\Email\Mailer\MailUser;
use AppBundle\Email\Mailer\MailUserFactory;
use AppBundle\Email\Mailer\Message;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class InvitationMail
{
    public function __construct(
        private readonly Mailer $mailer,
        private readonly TranslatorInterface $translator,
        private readonly RouterInterface $router,
    ) {}

    /**
     * Send mail to please a user to join a company as a member
     *
     * @param PersonneMorale $companyMember The company who sends the invitation
     * @param PersonneMoraleInvitation $invitation The invitation to send
     */
    public function sendInvitation(PersonneMorale $companyMember, PersonneMoraleInvitation $invitation): bool
    {
        $text = $this->translator->trans('mail.invitationMembership.text',
            [
                '%firstname%' => $companyMember->firstName,
                '%lastname%' => $companyMember->lastName,
                '%link%' => $this->router->generate(
                    'company_invitation',
                    ['invitationId' => $invitation->id, 'token' => $invitation->token],
                    UrlGeneratorInterface::ABSOLUTE_URL,
                ),
            ],
        );

        return $this->mailer->sendTransactional(new Message(
            sprintf('%s vous invite à profiter de son compte "Membre AFUP"', $companyMember->companyName),
            MailUserFactory::sponsors(),
            new MailUser($invitation->email),
        ), $text);
    }
}
