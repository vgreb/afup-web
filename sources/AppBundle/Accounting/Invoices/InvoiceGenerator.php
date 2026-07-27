<?php

declare(strict_types=1);

namespace AppBundle\Accounting\Invoices;

use AppBundle\Accounting\Invoices\Dto\InvoiceData;
use AppBundle\Accounting\Invoices\Generator\InvoiceGeneration;
use AppBundle\Association\Entity\PersonneMorale;
use AppBundle\Association\Model\User;

class InvoiceGenerator
{
    /**
     * @param iterable<InvoiceGeneration> $handlers
     */
    public function __construct(private readonly iterable $handlers) {}

    public function getInvoiceData(User|PersonneMorale $user): InvoiceData
    {
        foreach ($this->handlers as $handler) {
            if ($handler->support($user)) {
                return $handler->generate($user);
            }
        }
        throw new \RuntimeException(sprintf(
            'No invoice generator supports member of type %s (id: %d).',
            $user::class,
            $user instanceof PersonneMorale ? $user->id : $user->getId(),
        ));
    }
}
