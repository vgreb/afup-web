<?php

declare(strict_types=1);

namespace AppBundle\Controller\Admin\Membership;

use AppBundle\Association\Entity\Repository\PersonneMoraleRepository;
use AppBundle\Association\Model\Repository\SubscriptionReminderLogRepository;
use AppBundle\Association\Model\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class ReminderLogAction
{
    public function __construct(
        private readonly SubscriptionReminderLogRepository $subscriptionReminderLogRepository,
        private readonly PersonneMoraleRepository $personneMoraleRepository,
        private readonly Environment $twig,
    ) {}

    public function __invoke(Request $request): Response
    {
        $page = $request->attributes->getInt('page', 1);
        $limit = 50;

        $logs = $this->subscriptionReminderLogRepository->getPaginatedLogs($page, $limit);

        /** @var iterable<array{app: ?User}> $logs */
        $companies = [];
        foreach ($logs as $log) {
            $companyId = $log['app']?->getCompanyId();
            if ($companyId && !isset($companies[$companyId])) {
                $companies[$companyId] = $this->personneMoraleRepository->find($companyId);
            }
        }

        return new Response($this->twig->render('admin/relances/liste.html.twig', [
            'logs' => $logs,
            'companies' => $companies,
            'limit' => $limit,
            'page' => $page,
            'title' => 'Relances',
        ]));
    }
}
