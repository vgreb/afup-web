<?php

declare(strict_types=1);

namespace AppBundle\Command;

use AppBundle\Association\Entity\PersonneMorale;
use AppBundle\Association\Entity\Repository\PersonneMoraleRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCompanyMemberStateCommand extends Command
{
    public function __construct(private readonly PersonneMoraleRepository $personneMoraleRepository)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('update-company-member-state')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var PersonneMorale $companyMember */
        foreach ($this->personneMoraleRepository->loadAll() as $companyMember) {
            $hasUptoDateMembershipFee = $companyMember->hasUpToDateMembershipFee();
            $companyMember->status = $hasUptoDateMembershipFee ? 1 : 0;
            $this->personneMoraleRepository->save($companyMember);
        }

        return Command::SUCCESS;
    }
}
