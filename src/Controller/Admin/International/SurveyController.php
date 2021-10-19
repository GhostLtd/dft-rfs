<?php

namespace App\Controller\Admin\International;

use App\Entity\International\Survey;
use App\Entity\International\SurveyResponse;
use App\Form\Admin\InternationalSurvey\BusinessDetailsType;
use App\Form\Admin\InternationalSurvey\CorrespondenceDetailsType;
use App\Form\Admin\InternationalSurvey\FinalDetailsType;
use App\Form\Admin\InternationalSurvey\InitialDetailsType;
use App\Security\Voter\AdminSurveyVoter;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/irhs/surveys/{surveyId}")
 * @Entity("survey", expr="repository.find(surveyId)")
 * @IsGranted(AdminSurveyVoter::EDIT, subject="survey")
 */
class SurveyController extends AbstractController
{
    private const ROUTE_PREFIX = 'admin_international_survey_';
    public const ADD_ROUTE = self::ROUTE_PREFIX.'add';
    public const DELETE_ROUTE = self::ROUTE_PREFIX.'delete';
    public const LIST_ROUTE = self::ROUTE_PREFIX.'list';
    public const TRANSITION_ROUTE = self::ROUTE_PREFIX.'transition';
    public const VIEW_ROUTE = self::ROUTE_PREFIX.'view';
    public const ADD_NOTE_ROUTE = self::ROUTE_PREFIX.'addnote';
    public const DELETE_NOTE_ROUTE = self::ROUTE_PREFIX.'deletenote';
    public const FLAG_QA_ROUTE = self::ROUTE_PREFIX.'flag_qa';

    public const ENTER_INITIAL_ROUTE = self::ROUTE_PREFIX.'initial_enter';
    public const EDIT_CORRESPONDENCE_ROUTE = self::ROUTE_PREFIX.'correspondence_edit';
    public const EDIT_BUSINESS_ROUTE = self::ROUTE_PREFIX.'business_edit';
    public const EDIT_FINAL_DETAILS_ROUTE = self::ROUTE_PREFIX.'final_details_edit';

    protected EntityManagerInterface $entityManager;
    protected RequestStack $requestStack;

    public function __construct(EntityManagerInterface $entityManager, RequestStack $requestStack)
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    /**
     * @Route("/enter-initial-details", name=self::ENTER_INITIAL_ROUTE)
     */
    public function enterInitialDetails(Survey $survey): Response
    {
        $response = (new SurveyResponse());
        $survey->setResponse($response);

        $this->entityManager->persist($response);

        $redirectUrl = $this->getRedirectUrl($survey);
        return $this->handleRequest($survey, InitialDetailsType::class, "admin/international/surveys/enter-initial-details.html.twig", $redirectUrl);
    }

    /**
     * @Route("/edit-correspondence-details", name=self::EDIT_CORRESPONDENCE_ROUTE)
     */
    public function editCorrespondenceDetails(Survey $survey): Response
    {
        $redirectUrl = $this->getRedirectUrl($survey, "tab-correspondence");
        return $this->handleRequest($survey, CorrespondenceDetailsType::class, "admin/international/surveys/edit-correspondence-details.html.twig", $redirectUrl);
    }

    /**
     * @Route("/edit-business-details", name=self::EDIT_BUSINESS_ROUTE)
     */
    public function editBusinessDetails(Survey $survey): Response
    {
        $redirectUrl = $this->getRedirectUrl($survey, "tab-business-details");
        return $this->handleRequest($survey, BusinessDetailsType::class, "admin/international/surveys/edit-business-details.html.twig", $redirectUrl);
    }

    /**
     * @Route("/edit-final-details", name=self::EDIT_FINAL_DETAILS_ROUTE)
     */
    public function editFinalDetails(Survey $survey): Response
    {
        $redirectUrl = $this->getRedirectUrl($survey, 'tab-final-details');
        return $this->handleRequest(
            $survey,
            FinalDetailsType::class,
            'admin/international/surveys/edit-final-details.html.twig',
            $redirectUrl,
            true
        );
    }

    protected function handleRequest(Survey $survey, string $formClass, string $template, string $redirectUrl, bool $formUsesSurvey = false) {
        $form = $this->createForm($formClass, $formUsesSurvey ? $survey : $this->getResponse($survey));
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
            'survey' => $survey,
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
}