<?php

declare(strict_types=1);

namespace AppBundle\Accounting\Invoices\Generator;

use AppBundle\Accounting\Invoices\Dto\InvoiceData;
use AppBundle\Association\Entity\PersonneMorale;
use AppBundle\Association\Model\User;

interface InvoiceGeneration
{
    public function generate(User|PersonneMorale $member): InvoiceData;

    public function support(User|PersonneMorale $user): bool;
}
