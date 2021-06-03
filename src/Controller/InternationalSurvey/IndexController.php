<?php

namespace App\Controller\InternationalSurvey;

use App\Entity\PasscodeUser;
use App\Security\Voter\SurveyVoter;
use App\Utility\International\PdfHelper;
use App\Workflow\InternationalSurvey\InitialDetailsState;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route("/international-survey")
 */
class IndexController extends AbstractController
{
    use SurveyHelperTrait;

    public const COMPLETED_ROUTE = 'app_internationalsurvey_completed';
    public const COMPLETED_PDF_ROUTE = 'app_internationalsurvey_completedpdf';
    public const SUMMARY_ROUTE = 'app_internationalsurvey_summary';

    /**
     * @Route("", name=self::SUMMARY_ROUTE)
     */
    public function index(UserInterface $user): Response {
        $survey = $this->getSurvey($user);

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
     * @Security("is_granted('VIEW_SUBMISSION_SUMMARY', user.getInternationalSurvey())")
     */
    public function completed(UserInterface $user): Response {
        $survey = $this->getSurvey($user);
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
        /** @var PasscodeUser $user */
        $user = $this->getUser();
        $survey = $user->getInternationalSurvey();
        $pdf = $pdfHelper->getMostRecentSurveyPDF($survey);

        if (!$pdf) {
            throw new NotFoundHttpException();
        }

        return new RedirectResponse($pdf->getStorageObject()->signedUrl(new \DateTime('+30 seconds')));
    }

}