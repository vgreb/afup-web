<?php

declare(strict_types=1);

namespace AppBundle\Association\CompanyMembership;

use AppBundle\MembershipFee\MembershipFeeService;
use Afup\Site\Utils\Utils;
use AppBundle\Association\Entity\PersonneMorale;
use AppBundle\Association\MemberType;
use AppBundle\MembershipFee\Model\MembershipFee;
use Webmozart\Assert\Assert;

final readonly class SubscriptionManagement
{
    public const int AFUP_COTISATION_PERSONNE_MORALE = 150;
    public const int AFUP_COTISATION_PERSONNE_PHYSIQUE = 30;
    public const int AFUP_PERSONNE_MORALE_SEUIL = 3;

    public function __construct(private MembershipFeeService $membershipFeeService) {}

    public function createInvoiceForInscription(PersonneMorale $company, int $numberOfMembers): array
    {
        Assert::notNull($company->id);

        $endSubscription = $this->membershipFeeService->getNextSubscriptionExpiration(null);

        // Create the invoice
        $this->membershipFeeService->ajouter(
            MemberType::MemberCompany,
            $company->id,
            ceil($numberOfMembers / self::AFUP_PERSONNE_MORALE_SEUIL) * self::AFUP_COTISATION_PERSONNE_MORALE * (1 + Utils::MEMBERSHIP_FEE_VAT_RATE),
            null,
            null,
            new \DateTime()->getTimestamp(),
            $endSubscription->getTimestamp(),
            '',
        );
        $subscription = $this->membershipFeeService->getLatestByUserTypeAndId(MemberType::MemberCompany, $company->id);

        if (!$subscription instanceof MembershipFee) {
            throw new \RuntimeException('An error occured');
        }

        return ['invoice' => $subscription->getInvoiceNumber(), 'token' => $subscription->getToken()];
    }
}
