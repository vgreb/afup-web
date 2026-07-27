<?php

declare(strict_types=1);

namespace AppBundle\Controller\Website\CompanyPublicProfile;

use AppBundle\Association\Entity\Repository\PersonneMoraleRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class LogoAction extends CompanyPublicProfileController
{
    public function __construct(
        PersonneMoraleRepository $personneMoraleRepository,
        #[Autowire('%app.members_logo_dir%')]
        private readonly string $storageDir,
    ) {
        parent::__construct($personneMoraleRepository);
    }

    public function __invoke(int $id, string $slug): BinaryFileResponse
    {
        $companyMember = $this->checkAndGetCompanyMember($id, $slug);

        $filepath = $this->storageDir . DIRECTORY_SEPARATOR . $companyMember->logoUrl;

        if (false === is_file($filepath)) {
            throw $this->createNotFoundException();
        }

        return new BinaryFileResponse($filepath);
    }
}
