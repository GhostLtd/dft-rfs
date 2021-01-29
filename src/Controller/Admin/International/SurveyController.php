<?php

namespace App\Controller\Admin\International;

use App\Entity\International\Survey;
use App\Entity\International\SurveyResponse;
use App\Form\Admin\InternationalSurvey\BusinessDetailsType;
use App\Form\Admin\InternationalSurvey\CorrespondenceDetailsType;
use App\Form\Admin\InternationalSurvey\InitialDetailsType;
use App\ListPage\International\SurveyListPage;
use App\Utility\ConfirmAction\International\Admin\SurveyWorkflowConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/irhs/surveys")
 */
class SurveyController extends AbstractController
{
    private const ROUTE_PREFIX = 'admin_international_survey_';
    public const DELETE_ROUTE = self::ROUTE_PREFIX.'delete';
    public const LIST_ROUTE = self::ROUTE_PREFIX.'list';
    public const VIEW_ROUTE = self::ROUTE_PREFIX.'view';

    public const ENTER_INITIAL_ROUTE = self::ROUTE_PREFIX.'initial_enter';

    public const EDIT_CORRESPONDENCE_ROUTE = self::ROUTE_PREFIX.'correspondence_edit';
    public const EDIT_BUSINESS_ROUTE = self::ROUTE_PREFIX.'business_edit';

    protected EntityManagerInterface $entityManager;
    protected RequestStack $requestStack;

    public function __construct(EntityManagerInterface $entityManager, RequestStack $requestStack)
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    /**
     * @Route("", name=self::LIST_ROUTE)
     */
    public function list(SurveyListPage $listPage, Request $request): Response
    {
        $listPage
            ->handleRequest($request);

        if ($listPage->isClearClicked()) {
            return new RedirectResponse($listPage->getClearUrl());
        }

        return $this->render('admin/international/surveys/list.html.twig', [
            'data' => $listPage->getData(),
            'form' => $listPage->getFiltersForm()->createView(),
        ]);
    }

    /**
     * @Route("/{surveyId}", name=self::VIEW_ROUTE)
     * @Entity("survey", expr="repository.findWithVehiclesAndTrips(surveyId)")
     */
    public function view(Survey $survey): Response
    {
        return $this->render('admin/international/surveys/view.html.twig', [
            'survey' => $survey,
        ]);
    }

    /**
     * @Route("/{surveyId}/enter-initial-details", name=self::ENTER_INITIAL_ROUTE)
     * @Entity("survey", expr="repository.find(surveyId)")
     */
    public function enterInitialDetails(Survey $survey): Response
    {
        $response = (new SurveyResponse())
            ->setSurvey($survey);

        $this->entityManager->persist($response);

        $redirectUrl = $this->getRedirectUrl($survey);
        return $this->handleRequest($response, InitialDetailsType::class, "admin/international/surveys/enter-initial-details.html.twig", $redirectUrl);
    }

    /**
     * @Route("/{surveyId}/edit-correspondence-details", name=self::EDIT_CORRESPONDENCE_ROUTE)
     * @Entity("survey", expr="repository.find(surveyId)")
     */
    public function editCorrespondenceDetails(Survey $survey): Response
    {
        $redirectUrl = $this->getRedirectUrl($survey, "tab-correspondence");
        return $this->handleRequest($this->getResponse($survey), CorrespondenceDetailsType::class, "admin/international/surveys/edit-correspondence-details.html.twig", $redirectUrl);
    }

    /**
     * @Route("/{surveyId}/edit-business-details", name=self::EDIT_BUSINESS_ROUTE)
     * @Entity("survey", expr="repository.find(surveyId)")
     */
    public function editBusinessDetails(Survey $survey): Response
    {
        $redirectUrl = $this->getRedirectUrl($survey, "tab-business-details");
        return $this->handleRequest($this->getResponse($survey), BusinessDetailsType::class, "admin/international/surveys/edit-business-details.html.twig", $redirectUrl);
    }

    protected function handleRequest(SurveyResponse $response, string $formClass, string $template, string $redirectUrl) {
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

        return $this->render($template, [
            'survey' => $response->getSurvey(),
            'form' => $form->createView(),
        ]);
    }

    protected function getRedirectUrl(Survey $survey, ?string $hash = null): string
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


    /**
     * @Route("/survey/{surveyId}/workflow/{transition}", name="admin_international_survey_workflow",
     *     requirements={"transition": "complete|re_open|approve|reject|un_reject|un_approve"}
     * )
     * @Entity("survey", expr="repository.find(surveyId)")
     * @Template("admin/international/survey/workflow-action.html.twig")
     */
    public function complete(SurveyWorkflowConfirmAction $surveyWorkflowConfirmAction, Request $request, Survey $survey, $transition)
    {
        $surveyWorkflowConfirmAction
            ->setSubject($survey)
            ->setTransition($transition)
        ;
        return $surveyWorkflowConfirmAction->controller(
            $request,
            function() use ($survey) { return $this->generateUrl(
                SurveyController::VIEW_ROUTE,
                ['surveyId' => $survey->getId()]
            );}
        );
    }
}