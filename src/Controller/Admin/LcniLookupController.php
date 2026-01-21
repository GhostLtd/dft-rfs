<?php

namespace App\Controller\Admin;

use App\Utility\EmailBlacklistService;
use App\Utility\LcniDisplayService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/api')]
class LcniLookupController extends AbstractController
{
    public function __construct(
        private readonly LcniDisplayService $lcniDisplayService,
        private readonly EmailBlacklistService $emailBlacklistService,
    ) {
    }

    #[Route('/lcni-lookup', name: 'admin_lcni_lookup', methods: ['GET'])]
    public function lookup(Request $request): JsonResponse
    {
        $companyName = $request->query->get('companyName');
        $surveyType = $request->query->get('surveyType');

        if (empty($companyName)) {
            return new JsonResponse(['error' => 'companyName is required'], Response::HTTP_BAD_REQUEST);
        }

        if (empty($surveyType)) {
            return new JsonResponse(['error' => 'surveyType is required'], Response::HTTP_BAD_REQUEST);
        }

        if (!in_array($surveyType, ['csrgt', 'irhs'], true)) {
            return new JsonResponse(['error' => 'surveyType must be csrgt or irhs'], Response::HTTP_BAD_REQUEST);
        }

        $displayInfo = $this->lcniDisplayService->getDisplayInfoForCompanyName($companyName, $surveyType);

        // Service returns ['locked' => true] if DVLA import is in progress
        if ($displayInfo !== null && isset($displayInfo['locked'])) {
            return new JsonResponse(['locked' => true]);
        }

        if ($displayInfo === null) {
            return new JsonResponse(['found' => false]);
        }

        $emails = $displayInfo['emails'];
        $blacklistedEmails = $this->emailBlacklistService->getBlacklistedFromList($emails);

        return new JsonResponse([
            'found' => true,
            'emails' => $emails,
            'blacklistedEmails' => $blacklistedEmails,
        ]);
    }

    #[Route('/email-blacklist-check', name: 'admin_email_blacklist_check', methods: ['GET'])]
    public function checkBlacklist(Request $request): JsonResponse
    {
        $emailsParam = $request->query->get('emails', '');
        if (empty($emailsParam)) {
            return new JsonResponse(['blacklistedEmails' => []]);
        }

        // Split by comma and clean up
        $emails = array_filter(array_map('trim', explode(',', $emailsParam)));
        if (empty($emails)) {
            return new JsonResponse(['blacklistedEmails' => []]);
        }

        $blacklistedEmails = $this->emailBlacklistService->getBlacklistedFromList($emails);

        return new JsonResponse([
            'blacklistedEmails' => $blacklistedEmails,
        ]);
    }
}
