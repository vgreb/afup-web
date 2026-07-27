<?php

declare(strict_types=1);

namespace AppBundle\Association\UserMembership;

use AppBundle\Association\Entity\PersonneMorale;
use AppBundle\Association\Entity\Repository\PersonneMoraleRepository;
use AppBundle\Association\Model\Repository\UserRepository;
use AppBundle\Association\Model\User;

class StatisticsComputer
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly PersonneMoraleRepository $personneMoraleRepository,
    ) {}

    public function computeStatistics(): Statistics
    {
        $statistics = new Statistics();
        /** @var User[] $users */
        $users = $this->userRepository->getActiveMembers();
        foreach ($users as $user) {
            $statistics->usersCount++;
            if ($user->isMemberForCompany()) {
                $statistics->usersCountWithCompanies++;

                if (isset($companies[$user->getCompanyId()]) === false) {
                    $companies[$user->getCompanyId()] = true;
                    $statistics->companiesCountWithLinkedUsers++;
                }
            } else {
                $statistics->usersCountWithoutCompanies++;
            }
        }
        $statistics->companiesCount = $this->personneMoraleRepository->countByStatus(PersonneMorale::STATUS_ACTIVE);

        return $statistics;
    }
}
