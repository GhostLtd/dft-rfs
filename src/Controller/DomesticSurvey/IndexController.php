<?php

namespace App\Controller\DomesticSurvey;

use App\Attribute\Redirect;
use App\Repository\MaintenanceWarningRepository;
use App\Utility\Domestic\PdfHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/domestic-survey')]
class IndexController extends AbstractController
{
    use SurveyHelperTrait;

    private const string ROUTE_PREFIX = 'app_domesticsurvey_';
    public const CONTACT_AND_BUSINESS_ROUTE = self::ROUTE_PREFIX."contactdetails";
    public const COMPLETED_ROUTE = self::ROUTE_PREFIX."completed";
    public const COMPLETED_PDF_ROUTE = self::ROUTE_PREFIX."completedpdf";
    public const SUMMARY_ROUTE = self::ROUTE_PREFIX."summary";

    #[Redirect("is_granted('VIEW_SUBMISSION_SUMMARY', user.getDomesticSurvey())", route: "app_domesticsurvey_completed")]
    #[IsGranted(new Expression("is_granted('EDIT', user.getDomesticSurvey())"))]
    #[Route(name: self::SUMMARY_ROUTE)]
    public function index(MaintenanceWarningRepository $maintenanceWarningRepository): Response
    {
        $survey = $this->getSurvey();

        if (!$survey->isInitialDetailsComplete()) {
            return $this->redirectToRoute("app_domesticsurvey_initialdetails_start");
        }

        return $this->render('domestic_survey/index.html.twig', [
            'survey' => $survey,
            'maintenanceWarningBanner' => $maintenanceWarningRepository->getNotificationBanner(),
        ]);
    }

    #[Redirect("is_granted('VIEW_SUBMISSION_SUMMARY', user.getDomesticSurvey())", route: "app_domesticsurvey_completed")]
    #[Redirect("!is_granted('ELIGIBLE_TO_FILL_SURVEY_WEEK', user.getDomesticSurvey())", route: "app_domesticsurvey_summary")]
    #[IsGranted(new Expression("is_granted('EDIT', user.getDomesticSurvey())"))]
    #[Route(path: '/contact-and-business-details', name: self::CONTACT_AND_BUSINESS_ROUTE)]
    public function contactAndBusinessDetails(): Response
    {
        $survey = $this->getSurvey();

        if (!$survey->isInitialDetailsComplete()) {
            return $this->redirectToRoute("app_domesticsurvey_initialdetails_start");
        }

        $response = $survey->getResponse();

        if (!$survey->isBusinessAndVehicleDetailsComplete() || $response->getIsExemptVehicleType()) {
            return $this->redirectToRoute(self::SUMMARY_ROUTE);
        }

        return $this->render('domestic_survey/contact-and-business-details.html.twig', [
            'survey' => $survey,
        ]);
    }

    #[Redirect("!is_granted('VIEW_SUBMISSION_SUMMARY', user.getDomesticSurvey())", route: "app_domesticsurvey_summary")]
    #[Route(path: '/completed', name: self::COMPLETED_ROUTE)]
    public function completed(): Response
    {
        return $this->render('domestic_survey/thanks.html.twig', [
            'survey' => $this->getSurvey(),
        ]);
    }

    #[IsGranted(new Expression("is_granted('VIEW_SUBMISSION_SUMMARY', user.getDomesticSurvey())"))]
    #[Route(path: '/completed.pdf', name: self::COMPLETED_PDF_ROUTE)]
    public function pdf(PdfHelper $pdfHelper, Request $request): Response
    {
        $survey = $this->getSurvey();

        if ($request->query->get('html')) {
            return new Response($pdfHelper->getSurveyPdfHtml($survey));
        }

        $pdf = $pdfHelper->getMostRecentSurveyPDF($survey);

        if (!$pdf) {
            throw new NotFoundHttpException();
        }

        return new RedirectResponse($pdf->getStorageObject()->signedUrl(new \DateTime('+30 seconds')));
    }
}
