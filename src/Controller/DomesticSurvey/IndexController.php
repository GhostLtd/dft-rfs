<?php

namespace App\Controller\DomesticSurvey;

use App\Annotation\Redirect;
use App\Security\Voter\SurveyVoter;
use App\Utility\Domestic\PdfHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/domestic-survey")
 */
class IndexController extends AbstractController
{
    use SurveyHelperTrait;

    private const ROUTE_PREFIX = 'app_domesticsurvey_';
    public const CONTACT_AND_BUSINESS_ROUTE = self::ROUTE_PREFIX."contactdetails";
    public const COMPLETED_ROUTE = self::ROUTE_PREFIX."completed";
    public const COMPLETED_PDF_ROUTE = self::ROUTE_PREFIX."completedpdf";
    public const SUMMARY_ROUTE = self::ROUTE_PREFIX."summary";

    /**
     * @Route(name=self::SUMMARY_ROUTE)
     * @Redirect("is_granted('VIEW_SUBMISSION_SUMMARY', user.getDomesticSurvey())", route="app_domesticsurvey_completed")
     * @Security("is_granted('EDIT', user.getDomesticSurvey())")
     */
    public function index(): Response
    {
        $survey = $this->getSurvey();

        if (!$survey->isInitialDetailsComplete()) {
            return $this->redirectToRoute("app_domesticsurvey_initialdetails_start");
        }

        return $this->render('domestic_survey/index.html.twig', [
            'survey' => $survey,
        ]);
    }

    /**
     * @Route("/contact-and-business-details", name=self::CONTACT_AND_BUSINESS_ROUTE)
     * @Redirect("is_granted('VIEW_SUBMISSION_SUMMARY', user.getDomesticSurvey())", route="app_domesticsurvey_completed")
     * @Security("is_granted('EDIT', user.getDomesticSurvey())")
     */
    public function contactAndBusinessDetails(): Response
    {
        $survey = $this->getSurvey();
        if (!$survey->isInitialDetailsComplete()) {
            return $this->redirectToRoute("app_domesticsurvey_initialdetails_start");
        }

        if (!$survey->isBusinessAndVehicleDetailsComplete()) {
            return $this->redirectToRoute(self::SUMMARY_ROUTE);
        }

        return $this->render('domestic_survey/contact-and-business-details.html.twig', [
            'survey' => $survey,
        ]);
    }

    /**
     * @Redirect("!is_granted('VIEW_SUBMISSION_SUMMARY', user.getDomesticSurvey())", route="app_domesticsurvey_summary")
     * @Route("/completed", name=self::COMPLETED_ROUTE)
     */
    public function completed(): Response
    {
        return $this->render('domestic_survey/thanks.html.twig', [
            'survey' => $this->getSurvey(),
        ]);
    }

    /**
     * @Security("is_granted('VIEW_SUBMISSION_SUMMARY', user.getDomesticSurvey())")
     * @Route("/completed.pdf", name=self::COMPLETED_PDF_ROUTE)
     */
    public function pdf(PdfHelper $pdfHelper): Response
    {
        $survey = $this->getSurvey();
        $pdf = $pdfHelper->getMostRecentSurveyPDF($survey);

        if (!$pdf) {
            throw new NotFoundHttpException();
        }

        return new RedirectResponse($pdf->getStorageObject()->signedUrl(new \DateTime('+30 seconds')));
    }
}
