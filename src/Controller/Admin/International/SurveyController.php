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
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/irhs/surveys/{surveyId}')]
#[IsGranted(AdminSurveyVoter::EDIT, subject: 'survey')]
class SurveyController extends AbstractController
{
    public const string ADD_ROUTE = 'admin_international_survey_add';
    public const string LIST_ROUTE = 'admin_international_survey_list';
    public const string TRANSITION_ROUTE = 'admin_international_survey_transition';
    public const string VIEW_ROUTE = 'admin_international_survey_view';
    public const string DELETE_NOTE_ROUTE = 'admin_international_survey_deletenote';
    public const string FLAG_QA_ROUTE = 'admin_international_survey_flag_qa';

    public const string ENTER_INITIAL_ROUTE = 'admin_international_survey_initial_enter';
    public const string EDIT_CORRESPONDENCE_ROUTE = 'admin_international_survey_correspondence_edit';
    public const string EDIT_BUSINESS_ROUTE = 'admin_international_survey_business_edit';
    public const string EDIT_FINAL_DETAILS_ROUTE = 'admin_international_survey_final_details_edit';

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected RequestStack           $requestStack
    ) {}

    #[Route(path: '/enter-initial-details', name: self::ENTER_INITIAL_ROUTE)]
    public function enterInitialDetails(
        #[MapEntity(expr: "repository.find(surveyId)")]
        Survey $survey
    ): Response
    {
        $response = (new SurveyResponse());
        $survey->setResponse($response);

        $this->entityManager->persist($response);

        $redirectUrl = $this->getRedirectUrl($survey);
        return $this->handleRequest($survey, InitialDetailsType::class, "admin/international/surveys/enter-initial-details.html.twig", $redirectUrl);
    }

    #[Route(path: '/edit-correspondence-details', name: self::EDIT_CORRESPONDENCE_ROUTE)]
    public function editCorrespondenceDetails(
        #[MapEntity(expr: "repository.find(surveyId)")]
        Survey $survey
    ): Response
    {
        $redirectUrl = $this->getRedirectUrl($survey, "tab-correspondence");
        return $this->handleRequest($survey, CorrespondenceDetailsType::class, "admin/international/surveys/edit-correspondence-details.html.twig", $redirectUrl);
    }

    #[Route(path: '/edit-business-details', name: self::EDIT_BUSINESS_ROUTE)]
    public function editBusinessDetails(
        #[MapEntity(expr: "repository.find(surveyId)")]
        Survey $survey
    ): Response
    {
        $redirectUrl = $this->getRedirectUrl($survey, "tab-business-details");
        return $this->handleRequest($survey, BusinessDetailsType::class, "admin/international/surveys/edit-business-details.html.twig", $redirectUrl);
    }

    #[Route(path: '/edit-final-details', name: self::EDIT_FINAL_DETAILS_ROUTE)]
    public function editFinalDetails(
        #[MapEntity(expr: "repository.find(surveyId)")]
        Survey $survey
    ): Response
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

    protected function handleRequest(Survey $survey, string $formClass, string $template, string $redirectUrl, bool $formUsesSurvey = false): Response
    {
        $form = $this->createForm($formClass, $formUsesSurvey ? $survey : $this->getResponse($survey));
        $request = $this->requestStack->getCurrentRequest();

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);
            $cancel = $form->get('buttons')->get('cancel');
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
            'form' => $form,
        ]);
    }

    protected function getRedirectUrl(Survey $survey, ?string $hash = null): string
    {
        return $this->generateUrl(self::VIEW_ROUTE, ['surveyId' => $survey->getId()]) . ($hash ? ("#" . $hash) : null);
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
