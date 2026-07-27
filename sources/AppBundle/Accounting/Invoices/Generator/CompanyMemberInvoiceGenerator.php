<?php

declare(strict_types=1);

namespace AppBundle\Accounting\Invoices\Generator;

use AppBundle\Accounting\Invoices\Dto\InvoiceData;
use AppBundle\Association\Entity\PersonneMorale;
use AppBundle\Association\Model\User;
use Webmozart\Assert\Assert;

class CompanyMemberInvoiceGenerator implements InvoiceGeneration
{
    public function generate(User|PersonneMorale $member): InvoiceData
    {
        Assert::isInstanceOf($member, PersonneMorale::class);

        return new InvoiceData(
            $member->companyName,
            $member->address,
            $member->zipCode,
            $member->city,
            $member->companyName,
        );
    }

    public function support(User|PersonneMorale $user): bool
    {
        return $user instanceof PersonneMorale;
    }
}
