<?php

namespace App\Controller\DomesticSurvey;

use App\Entity\PasscodeUser;
use App\Security\Voter\Domestic\SurveyVoter;
use Dompdf\Dompdf;
use Dompdf\Options;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * Class IndexController
 * @package App\Controller\DomesticSurvey
 * @Route("/domestic-survey")
 */
class IndexController extends AbstractController
{
    /**
     * @Route(name="app_domesticsurvey_index")
     * @return Response
     */
    public function index(): Response
    {
        /** @var PasscodeUser $user */
        $user = $this->getUser();
        $domesticSurvey = $user->getDomesticSurvey();

        if (!$this->isGranted(SurveyVoter::EDIT, $domesticSurvey)) {
            $this->denyAccessUnlessGranted(SurveyVoter::VIEW_SUBMISSION_SUMMARY, $domesticSurvey);
            return $this->redirectToRoute("app_domesticsurvey_closed");
        }

        if (!$domesticSurvey->isInitialDetailsComplete()) {
            // start wizard
            return $this->redirectToRoute("app_domesticsurvey_initialdetails_start");
        }

        // show summary
        return $this->render('domestic_survey/index.html.twig', [
            'survey' => $domesticSurvey,
        ]);
    }

    /**
     * @Route("/contact-and-business-details", name="app_domesticsurvey_contactdetails")
     * @Security("is_granted('EDIT', user.getDomesticSurvey())")
     * @return Response
     */
    public function contactAndBusinessDetails(): Response
    {
        /** @var PasscodeUser $user */
        $user = $this->getUser();
        $domesticSurvey = $user->getDomesticSurvey();

        if (!$domesticSurvey->isInitialDetailsComplete()) {
            // start wizard
            return $this->redirectToRoute("app_domesticsurvey_initialdetails_start");
        }

        if (!$domesticSurvey->isBusinessAndVehicleDetailsComplete()) {
            return $this->redirectToRoute('app_domesticsurvey_index');
        }

        // show summary
        return $this->render('domestic_survey/contact-and-business-details.html.twig', [
            'survey' => $domesticSurvey,
        ]);
    }

    /**
     * @return Response
     * @Security("is_granted('VIEW_SUBMISSION_SUMMARY', user.getDomesticSurvey())")
     * @Route("/completed", name="app_domesticsurvey_closed")
     */
    public function completed(): Response
    {
        /** @var PasscodeUser $user */
        $user = $this->getUser();
        $domesticSurvey = $user->getDomesticSurvey();

        // show summary
        return $this->render('domestic_survey/completed.html.twig', [
            'survey' => $domesticSurvey,
        ]);
    }

    /**
     * @Route("/view-whole-survey")
     */
    public function wholeSurvey($frontendHostname, ?Profiler $profiler, $appEnvironment)
    {
        if(!in_array($appEnvironment, array('prod', 'staging'))) {
//            $profiler->disable();
        }

//        /** @var PasscodeUser $user */
//        $user = $this->getUser();
//        $domesticSurvey = $user->getDomesticSurvey();

//        return new BinaryFileResponse($dompdf->stream());
        return new Response(dump(trim($this->renderView('domestic_survey/view-whole-survey.html.twig', []))));
    }

    /**
     * @Route("/view-whole-survey-pdf")
     */
    public function wholeSurveyPdf($appEnvironment)
    {
        // instantiate and use the dompdf class
        $dompdf = new Dompdf();
        $options = $dompdf->getOptions();
        $options->setIsHtml5ParserEnabled(true);
        $options->setIsRemoteEnabled(true);

        $url = $this->generateUrl('app_domesticsurvey_index_wholesurvey', [], UrlGeneratorInterface::ABSOLUTE_URL);
        if ($appEnvironment === 'dev') {
            $response = file_get_contents($url, false, stream_context_create([
                "ssl" => [
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ]
            ]));
            $dompdf->loadHtml($response);
        } else {
            $dompdf->loadHtmlFile($url);
        }
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream();
        exit;
    }

}
