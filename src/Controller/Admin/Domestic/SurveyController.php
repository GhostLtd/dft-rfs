<?php

namespace App\Controller\Admin\Domestic;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\Domestic\Vehicle;
use App\Form\Admin\DomesticSurvey\Edit\BusinessAndVehicleDetailsType;
use App\Form\Admin\DomesticSurvey\Edit\InitialDetailsType;
use App\Form\Admin\DomesticSurvey\Edit\BusinessDetailsType;
use App\Form\Admin\DomesticSurvey\Edit\VehicleDetailsType;
use App\Form\Admin\DomesticSurvey\FinalDetailsType;
use App\Security\Voter\AdminSurveyVoter;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/csrgt/surveys/{surveyId}")
 * @Entity("survey", expr="repository.find(surveyId)")
 * @IsGranted(AdminSurveyVoter::EDIT, subject="survey")
 */
class SurveyController extends AbstractController
{
    private const ROUTE_PREFIX = 'admin_domestic_survey_';
    public const ADD_ROUTE = self::ROUTE_PREFIX.'add';
    public const LIST_ROUTE = self::ROUTE_PREFIX.'list';
    public const LOGS_ROUTE = self::ROUTE_PREFIX.'logs';
    public const VIEW_ROUTE = self::ROUTE_PREFIX.'view';
    public const ADD_NOTE_ROUTE = self::ROUTE_PREFIX.'addnote';
    public const DELETE_NOTE_ROUTE = self::ROUTE_PREFIX.'deletenote';

    public const ENTER_INITIAL_ROUTE = self::ROUTE_PREFIX.'initial_enter';
    public const ENTER_BUSINESS_AND_VEHICLE_ROUTE = self::ROUTE_PREFIX.'business_and_vehicle_enter';
    public const EDIT_FINAL_DETAILS_ROUTE = self::ROUTE_PREFIX.'final_details_edit';

    public const EDIT_BUSINESS_ROUTE = self::ROUTE_PREFIX.'business_edit';
    public const EDIT_INITIAL_ROUTE = self::ROUTE_PREFIX.'initial_edit';
    public const EDIT_VEHICLE_ROUTE = self::ROUTE_PREFIX.'vehicle_edit';

    public const TRANSITION_ROUTE = self::ROUTE_PREFIX.'transition';
    public const COMPLETE_ROUTE = self::ROUTE_PREFIX.'complete';
    public const FLAG_QA_ROUTE = self::ROUTE_PREFIX.'flag_qa';

    protected EntityManagerInterface $entityManager;
    protected RequestStack $requestStack;

    public function __construct(EntityManagerInterface $entityManager, RequestStack $requestStack)
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    /**
     * @Route("/enter-business-and-vehicle-details", name=self::ENTER_BUSINESS_AND_VEHICLE_ROUTE)
     * @Security("is_granted('ENTER_BUSINESS_AND_VEHICLE_DETAILS', survey)");
     */
    public function enterBusinessAndVehicle(Survey $survey): Response
    {
        $redirectUrl = $this->getRedirectUrl($survey, 'tab-business-details');
        return $this->handleRequest($this->getResponse($survey), BusinessAndVehicleDetailsType::class, 'admin/domestic/surveys/enter-business-and-vehicle-details.html.twig', $redirectUrl);
    }

    /**
     * @Route("/edit-final-details", name=self::EDIT_FINAL_DETAILS_ROUTE)
     */
    public function editFinalDetails(Survey $survey): Response
    {
        $redirectUrl = $this->getRedirectUrl($survey, 'tab-final-details');
        return $this->handleRequest($this->getResponse($survey), FinalDetailsType::class, 'admin/domestic/surveys/edit-final-details.html.twig', $redirectUrl);
    }

    /**
     * @Route("/enter-initial-details", name=self::ENTER_INITIAL_ROUTE)
     */
    public function enterInitialDetails(Survey $survey): Response
    {
        $redirectUrl = $this->getRedirectUrl($survey);
        $response = (new SurveyResponse())
            ->setSurvey($survey);

        $vehicle = (new Vehicle())
            ->setRegistrationMark($survey->getRegistrationMark())
            ->setResponse($response);

        $this->entityManager->persist($response);
        $this->entityManager->persist($vehicle);

        return $this->handleRequest($response, InitialDetailsType::class, 'admin/domestic/surveys/enter-initial-details.html.twig', $redirectUrl);
    }

    /**
     * @Route("/edit-initial-details", name=self::EDIT_INITIAL_ROUTE)
     * @IsGranted(AdminSurveyVoter::EDIT, subject="survey")
     */
    public function editInitialDetails(Survey $survey): Response
    {
        $redirectUrl = $this->getRedirectUrl($survey, 'tab-initial-details');
        return $this->handleRequest($this->getResponse($survey),InitialDetailsType::class, 'admin/domestic/surveys/edit-initial-details.html.twig', $redirectUrl);
    }

    /**
     * @Route("/edit-business-details", name=self::EDIT_BUSINESS_ROUTE)
     */
    public function editBusinessDetails(Survey $survey): Response
    {
        $redirectUrl = $this->getRedirectUrl($survey, 'tab-business-details');
        return $this->handleRequest($this->getResponse($survey), BusinessDetailsType::class, 'admin/domestic/surveys/edit-business-details.html.twig', $redirectUrl);
    }

    /**
     * @Route("/edit-vehicle-details", name=self::EDIT_VEHICLE_ROUTE)
     */
    public function editVehicleDetails(Survey $survey): Response
    {
        $redirectUrl = $this->getRedirectUrl($survey, 'tab-vehicle-details');
        return $this->handleRequest($this->getResponse($survey), VehicleDetailsType::class, 'admin/domestic/surveys/edit-vehicle-details.html.twig', $redirectUrl);
    }

    protected function handleRequest(SurveyResponse $response, string $formClass, string $templateName, string $redirectUrl) {
        $form = $this->createForm($formClass, $response);
        $request = $this->requestStack->getCurrentRequest();

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            $cancel = $form->get('cancel');
            if ($cancel instanceof SubmitButton && $cancel->isClicked()) {
                return new RedirectResponse($redirectUrl);
            };

            if ($form->isValid()) {
                $this->entityManager->flush();
                return new RedirectResponse($redirectUrl);
            }
        }

        return $this->render($templateName, [
            'survey' => $response->getSurvey(),
            'form' => $form->createView(),
        ]);
    }

    protected function getRedirectUrl(Survey $survey, string $hash = null): string
    {
        return $this->generateUrl(self::VIEW_ROUTE, ['surveyId' => $survey->getId()]) . ($hash ? ("#".$hash) : null);
    }

    protected function getResponse(Survey $survey): SurveyResponse
    {
        $response = $survey->getResponse();

        if (!$response) {
            throw new NotFoundHttpException();
        }

        return $response;
    }
}
