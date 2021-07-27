<?php

namespace App\Controller;

use App\Controller\DomesticSurvey\IndexController as DomesticIndexController;
use App\Controller\PreEnquiry\PreEnquiryController;
use App\Controller\InternationalSurvey\IndexController as InternationalIndexController;
use App\Entity\PasscodeUser;
use App\Repository\MaintenanceWarningRepository;
use App\Utility\SessionTimeoutHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @Route("/")
     */
    public function index(MaintenanceWarningRepository $maintenanceWarningRepository): Response
    {
        $user = $this->getUser();
        if ($user && $user instanceof PasscodeUser) {
            if ($user->getDomesticSurvey()) {
                return $this->redirectToRoute(DomesticIndexController::SUMMARY_ROUTE);
            } else if ($user->getInternationalSurvey()) {
                return $this->redirectToRoute(InternationalIndexController::SUMMARY_ROUTE);
            } else if ($user->getPreEnquiry()) {
                return $this->redirectToRoute(PreEnquiryController::SUMMARY_ROUTE);
            }
        }

        return $this->render('home/index.html.twig', [
            'maintenanceWarningBanner' => $maintenanceWarningRepository->getNotificationBannerForFrontend(),
        ]);
    }

    /**
     * @Route("/sitemap.txt")
     */
    public function sitemap(): Response
    {
        return $this->render('home/sitemap.txt.twig');
    }

    /**
     * @Route("/refresh-session")
     */
    public function refreshSession(SessionTimeoutHelper $timeoutHelper): JsonResponse
    {
        return new JsonResponse([
            'warning' => $timeoutHelper->getWarningTime(),
            'expiry' => $timeoutHelper->getExpiryTime(),
        ]);
    }

    /**
     * @Route("/accessibility-statement")
     * @Template("home/accessibility-statement.html.twig")
     */
    public function accessibilityStatement()
    {
        return [];
    }

    /**
     * @Route("/privacy-statement")
     * @Template("home/privacy-statement.html.twig")
     */
    public function privacyStatement()
    {
        return [];
    }
}
