<?php

declare(strict_types=1);

namespace AppBundle\Controller\Website\Member;

use AppBundle\Association\Entity\Repository\PersonneMoraleRepository;
use AppBundle\Association\Form\CompanyPublicProfile;
use AppBundle\Security\Authentication;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CompanyPublicProfileAction extends AbstractController
{
    public function __construct(
        private readonly PersonneMoraleRepository $personneMoraleRepository,
        #[Autowire('%app.members_logo_dir%')]
        private readonly string $storageDir,
        private readonly Authentication $authentication,
    ) {}

    public function __invoke(Request $request): Response
    {
        $companyMember = $this->personneMoraleRepository->find($this->authentication->getAfupUser()->getCompanyId());

        if ($companyMember === null) {
            throw $this->createNotFoundException("Company member not found");
        }

        $defaultData = [
            'enabled' => $companyMember->publicProfileEnabled,
            'description' => $companyMember->description,
            'website_url' => $companyMember->websiteUrl,
            'contact_page_url' => $companyMember->contactPageUrl,
            'careers_page_url' => $companyMember->careersPageUrl,
            'twitter_handle' => $companyMember->twitterHandle,
            'related_afup_offices' => $companyMember->getFormattedRelatedAfupOffices(),
            'membership_reason' => $companyMember->membershipReason,
        ];

        $formOptions = [
            'logo_required' => false === $companyMember->hasLogoUrl(),
        ];

        $form = $this->createForm(CompanyPublicProfile::class, $defaultData, $formOptions);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /**
             * @var array{enabled: bool, description: string, logo: ?UploadedFile, website_url: ?string, careers_page_url: ?string, contact_page_url: ?string, twitter_handle: ?string, related_afup_offices: string[], membership_reason: ?string} $data
             */
            $data = $form->getData();

            $uploadedFile = $data['logo'] ?? null;

            if ($uploadedFile instanceof UploadedFile) {
                $filename = $companyMember->id . '.' . $uploadedFile->getClientOriginalExtension();

                $uploadedFile->move(
                    $this->prepareUploadedFilesDir(),
                    $filename,
                );

                $companyMember->logoUrl = $filename;
            }

            $companyMember->publicProfileEnabled = $data['enabled'];
            $companyMember->description = $data['description'];
            $companyMember->websiteUrl = $data['website_url'];
            $companyMember->contactPageUrl = $data['contact_page_url'];
            $companyMember->careersPageUrl = $data['careers_page_url'];
            $companyMember->twitterHandle = $data['twitter_handle'];
            $companyMember->setFormattedRelatedAfupOffices($data['related_afup_offices']);
            $companyMember->membershipReason = $data['membership_reason'];

            $this->personneMoraleRepository->save($companyMember);

            $this->addFlash('success', 'Modifications enregistrées');
            return $this->redirectToRoute('member_company_public_profile');
        }

        return $this->render(
            'site/member/company_public_profile.html.twig',
            [
                'form' => $form->createView(),
                'company_member' => $companyMember,
            ],
        );
    }

    private function prepareUploadedFilesDir(): string
    {
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir);
        }

        return $this->storageDir;
    }
}
