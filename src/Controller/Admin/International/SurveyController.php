<?php

namespace App\Controller\Admin\International;

use App\Entity\International\Survey;
use App\Form\Admin\InternationalSurvey\BusinessDetailsType;
use App\Form\Admin\InternationalSurvey\CorrespondenceDetailsType;
use App\Form\Admin\InternationalSurvey\SurveyDeleteType;
use App\ListPage\International\SurveyListPage;
use App\Utility\International\DeleteHelper;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
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

    public const EDIT_CORRESPONDENCE_ROUTE = self::ROUTE_PREFIX.'correspondence_edit';
    public const EDIT_BUSINESS_ROUTE = self::ROUTE_PREFIX.'business_edit';

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
     * @Route("/{surveyId}/edit-correspondence-details", name=self::EDIT_CORRESPONDENCE_ROUTE)
     * @Entity("survey", expr="repository.find(surveyId)")
     */
    public function editCorrespondenceDetails(Survey $survey, Request $request, EntityManagerInterface $entityManager): Response
    {
        return $this->surveyEdit($survey, $request, $entityManager, CorrespondenceDetailsType::class, 'correspondence-details', 'tab-correspondence');
    }

    /**
     * @Route("/{surveyId}/edit-business-details", name=self::EDIT_BUSINESS_ROUTE)
     * @Entity("survey", expr="repository.find(surveyId)")
     */
    public function editBusinessDetails(Survey $survey, Request $request, EntityManagerInterface $entityManager): Response
    {
        return $this->surveyEdit($survey, $request, $entityManager, BusinessDetailsType::class, 'business-details', 'tab-business-details');
    }

    protected function surveyEdit(Survey $survey, Request $request, EntityManagerInterface $entityManager, string $formClass, string $templateName, string $redirectTab) {
        $response = $survey->getResponse();
        if (!$response) {
            throw new NotFoundHttpException();
        }

        $form = $this->createForm($formClass, $response);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            $isValid = $form->isValid();
            if ($isValid) {
                $entityManager->flush();
            }

            $cancel = $form->get('cancel');
            if ($isValid || ($cancel instanceof SubmitButton && $cancel->isClicked())) {
                return new RedirectResponse($this->generateUrl(SurveyController::VIEW_ROUTE, ['surveyId' => $survey->getId()]).'#'.$redirectTab);
            }
        }

        return $this->render("admin/international/surveys/edit-{$templateName}.html.twig", [
            'survey' => $survey,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/survey/{surveyId}/delete", name=self::DELETE_ROUTE)
     * @Entity("survey", expr="repository.find(surveyId)")
     */
    public function delete(Survey $survey, Request $request, DeleteHelper $deleteHelper): Response
    {
        $form = $this->createForm(SurveyDeleteType::class);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            $delete = $form->get('delete');
            if ($delete instanceof SubmitButton && $delete->isClicked()) {
                $deleteHelper->deleteSurvey($survey);

                $this->addFlash('notification', new NotificationBanner('Success', "Survey successfully deleted", "The survey was deleted.", ['type' => 'success']));
                return new RedirectResponse($this->generateUrl(SurveyController::LIST_ROUTE));
            } else {
                $this->addFlash('notification', new NotificationBanner('Important', 'Survey not deleted', "The request to delete this survey was cancelled."));
                return new RedirectResponse($this->generateUrl(SurveyController::VIEW_ROUTE, ['surveyId' => $survey->getId()]));
            }
        }

        return $this->render('admin/international/survey/delete.html.twig', [
            'survey' => $survey,
            'form' => $form->createView(),
        ]);
    }
}