<?php

declare(strict_types=1);

namespace AppBundle\Accounting\Invoices\Generator;

use AppBundle\Accounting\Invoices\Dto\InvoiceData;
use AppBundle\Association\Entity\PersonneMorale;
use AppBundle\Association\Model\User;
use Webmozart\Assert\Assert;

class MemberInvoiceGenerator implements InvoiceGeneration
{
    public function generate(User|PersonneMorale $member): InvoiceData
    {
        Assert::isInstanceOf($member, User::class);

        return new InvoiceData(
            $member->getFirstName() . ' ' . $member->getLastName(),
            $member->getAddress(),
            $member->getZipCode(),
            $member->getCity(),
            $member->getLastName(),
        );
    }

    public function support(User|PersonneMorale $user): bool
    {
        return $user instanceof User;
    }
}
