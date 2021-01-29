<?php

namespace App\Controller\Admin\Domestic;

use App\Entity\BlameLog\BlameLog;
use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\Domestic\Vehicle;
use App\Form\Admin\DomesticSurvey\Edit\BusinessAndVehicleDetailsType;
use App\Form\Admin\DomesticSurvey\Edit\InitialDetailsType;
use App\Form\Admin\DomesticSurvey\Edit\BusinessDetailsType;
use App\Form\Admin\DomesticSurvey\Edit\VehicleDetailsType;
use App\ListPage\Domestic\SurveyListPage;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/csrgt/surveys")
 */
class SurveyController extends AbstractController
{
    private const ROUTE_PREFIX = 'admin_domestic_survey_';
    public const DELETE_ROUTE = self::ROUTE_PREFIX.'delete';
    public const LIST_ROUTE = self::ROUTE_PREFIX.'list';
    public const LOGS_ROUTE = self::ROUTE_PREFIX.'logs';
    public const VIEW_ROUTE = self::ROUTE_PREFIX.'view';

    public const ENTER_INITIAL_ROUTE = self::ROUTE_PREFIX.'initial_enter';
    public const ENTER_BUSINESS_AND_VEHICLE_ROUTE = self::ROUTE_PREFIX.'business_and_vehicle_enter';

    public const EDIT_BUSINESS_ROUTE = self::ROUTE_PREFIX.'business_edit';
    public const EDIT_INITIAL_ROUTE = self::ROUTE_PREFIX.'initial_edit';
    public const EDIT_VEHICLE_ROUTE = self::ROUTE_PREFIX.'vehicle_edit';

    protected EntityManagerInterface $entityManager;
    protected RequestStack $requestStack;

    public function __construct(EntityManagerInterface $entityManager, RequestStack $requestStack)
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    /**
     * @Route("/{type}", requirements={"type": "gb|ni"}, name=self::LIST_ROUTE)
     */
    public function list(SurveyListPage $listPage, string $type): Response
    {
        $listPage
            ->setType($type)
            ->handleRequest($this->requestStack->getCurrentRequest());

        if ($listPage->isClearClicked()) {
            return new RedirectResponse($listPage->getClearUrl());
        }

        return $this->render('admin/domestic/surveys/list.html.twig', [
            'type' => $type,
            'data' => $listPage->getData(),
            'form' => $listPage->getFiltersForm()->createView(),
        ]);
    }

    /**
     * @Route("/{surveyId}", name=self::VIEW_ROUTE)
     * @Entity("survey", expr="repository.find(surveyId)")
     */
    public function viewDetails(Survey $survey): Response
    {
        return $this->render('admin/domestic/surveys/view.html.twig', [
            'survey' => $survey,
        ]);
    }

    /**
     * @Route("/{surveyId}/audit-log", name=self::LOGS_ROUTE)
     * @Entity("survey", expr="repository.find(surveyId)")
     */
    public function viewLog(EntityManagerInterface $blameLogEntityManager, Survey $survey): Response
    {
        $blameLogRepository = $blameLogEntityManager->getRepository(BlameLog::class);
        return $this->render('admin/domestic/surveys/log.html.twig', [
            'survey' => $survey,
            'log' => $blameLogRepository->getAllLogsForEntity($survey),
        ]);
    }

    /**
     * @Route("/{surveyId}/enter-business-and-vehicle-details", name=self::ENTER_BUSINESS_AND_VEHICLE_ROUTE)
     * @Entity("survey", expr="repository.find(surveyId)")
     */
    public function enterBusinessAndVehicle(Survey $survey): Response
    {
        $redirectUrl = $this->getRedirectUrl($survey, 'tab-business-details');
        return $this->handleRequest($this->getResponse($survey), BusinessAndVehicleDetailsType::class, 'admin/domestic/surveys/enter-business-and-vehicle-details.html.twig', $redirectUrl);
    }

    /**
     * @Route("/{surveyId}/enter-initial-details", name=self::ENTER_INITIAL_ROUTE)
     * @Entity("survey", expr="repository.find(surveyId)")
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
     * @Route("/{surveyId}/edit-initial-details", name=self::EDIT_INITIAL_ROUTE)
     * @Entity("survey", expr="repository.find(surveyId)")
     */
    public function editInitialDetails(Survey $survey): Response
    {
        $redirectUrl = $this->getRedirectUrl($survey, 'tab-initial-details');
        return $this->handleRequest($this->getResponse($survey),InitialDetailsType::class, 'admin/domestic/surveys/edit-initial-details.html.twig', $redirectUrl);
    }

    /**
     * @Route("/{surveyId}/edit-business-details", name=self::EDIT_BUSINESS_ROUTE)
     * @Entity("survey", expr="repository.find(surveyId)")
     */
    public function editBusinessDetails(Survey $survey): Response
    {
        $redirectUrl = $this->getRedirectUrl($survey, 'tab-business-details');
        return $this->handleRequest($this->getResponse($survey), BusinessDetailsType::class, 'admin/domestic/surveys/edit-business-details.html.twig', $redirectUrl);
    }

    /**
     * @Route("/{surveyId}/edit-vehicle-details", name=self::EDIT_VEHICLE_ROUTE)
     * @Entity("survey", expr="repository.find(surveyId)")
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
