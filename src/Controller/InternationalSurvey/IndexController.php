<?php

namespace App\Controller\InternationalSurvey;

use App\Security\Voter\SurveyVoter;
use App\Utility\International\PdfHelper;
use App\Workflow\InternationalSurvey\InitialDetailsState;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/international-survey")
 */
class IndexController extends AbstractController
{
    use SurveyHelperTrait;

    private const ROUTE_PREFIX = 'app_internationalsurvey_';
    public const COMPLETED_ROUTE = self::ROUTE_PREFIX.'completed';
    public const COMPLETED_PDF_ROUTE = self::ROUTE_PREFIX.'completedpdf';
    public const SUMMARY_ROUTE = self::ROUTE_PREFIX.'summary';

    /**
     * @Route("", name=self::SUMMARY_ROUTE)
     */
    public function index(): Response {
        $survey = $this->getSurvey();

        if ($this->isGranted(SurveyVoter::VIEW_SUBMISSION_SUMMARY, $survey)) {
            return $this->redirectToRoute(self::COMPLETED_ROUTE);
        }
        $this->denyAccessUnlessGranted(SurveyVoter::EDIT, $survey);

        if (!$survey->isInitialDetailsComplete()) {
            return $this->redirectToRoute(InitialDetailsController::WIZARD_ROUTE, ['state' => InitialDetailsState::STATE_INTRODUCTION]);
        }

        $response = $survey->getResponse();

        if (!$response->isInitialDetailsSignedOff()) {
            return $this->redirectToRoute(BusinessAndCorrespondenceDetailsController::SUMMARY_ROUTE);
        }

        $vehicles = $response->getVehicles();

        return $this->render('international_survey/summary.html.twig', [
            'response' => $response,
            'vehicles' => $vehicles,
        ]);
    }

    /**
     * @Route("/completed", name=self::COMPLETED_ROUTE)
     */
    public function completed(): Response {
        $survey = $this->getSurvey();

        if (!$this->isGranted(SurveyVoter::VIEW_SUBMISSION_SUMMARY, $survey)) {
            return new RedirectResponse($this->generateUrl(self::SUMMARY_ROUTE));
        }

        return $this->render('international_survey/thanks.html.twig', [
            'survey' => $survey,
        ]);
    }

    /**
     * @Route("/completed.pdf", name=self::COMPLETED_PDF_ROUTE)
     * @Security("is_granted('VIEW_SUBMISSION_SUMMARY', user.getInternationalSurvey())")
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