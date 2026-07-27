<?php

declare(strict_types=1);

namespace AppBundle\Tests\Association\Entity;

use AppBundle\Association\CompanyMembership\SubscriptionManagement;
use AppBundle\Association\Entity\PersonneMorale;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PersonneMoraleTest extends TestCase
{
    #[DataProvider('companies')]
    public function testMembershipFee(PersonneMorale $personneMorale, float $expectedAmount): void
    {
        self::assertEquals($expectedAmount, $personneMorale->getMembershipFee());
    }

    public static function companies(): array
    {
        return [
            'null' => [new PersonneMorale(), SubscriptionManagement::AFUP_COTISATION_PERSONNE_MORALE],
            'under' => [self::withMaxMembers(2),
                SubscriptionManagement::AFUP_COTISATION_PERSONNE_MORALE,
            ],
            'equal' => [self::withMaxMembers(3),
                SubscriptionManagement::AFUP_COTISATION_PERSONNE_MORALE,
            ],
            'just over' => [self::withMaxMembers(4), 2 * SubscriptionManagement::AFUP_COTISATION_PERSONNE_MORALE],
            'over' => [self::withMaxMembers(6), 2 * SubscriptionManagement::AFUP_COTISATION_PERSONNE_MORALE],
        ];
    }

    private static function withMaxMembers(int $maxMembers): PersonneMorale
    {
        $personneMorale = new PersonneMorale();
        $personneMorale->maxMembers = $maxMembers;

        return $personneMorale;
    }
}
