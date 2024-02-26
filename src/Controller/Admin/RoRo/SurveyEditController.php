<?php

namespace App\Controller\Admin\RoRo;

use App\Form\Admin\RoRo\EditIsActiveStateType;
use App\Form\Admin\RoRo\EditType;
use App\Entity\RoRo\Survey;
use App\Security\Voter\AdminSurveyVoter;
use App\Utility\RoRo\VehicleCountHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/roro/surveys/{surveyId}", name: "admin_roro_surveys_")]
#[IsGranted(AdminSurveyVoter::EDIT, subject: "survey")]
class SurveyEditController extends AbstractController
{
    public function __construct(protected EntityManagerInterface $entityManager)
    {}

    #[Route("/edit", name: "edit")]
    public function edit(
        Request $request,
        #[MapEntity(expr: "repository.find(surveyId)")]
        Survey $survey,
        VehicleCountHelper $vehicleCountHelper
    ): Response
    {
        $vehicleCountHelper->setVehicleCountLabels($survey->getVehicleCounts());

        return $this->handlePostAndGenerateResponse(
            $survey,
            $request,
            EditType::class,
            'admin/roro/surveys/edit.html.twig'
        );
    }

    #[Route("/edit-active-state", name: "edit_is_active")]
    public function editIsActiveState(
        Request $request,
        #[MapEntity(expr: "repository.find(surveyId)")]
        Survey $survey
    ): Response
    {
        return $this->handlePostAndGenerateResponse(
            $survey,
            $request,
            EditIsActiveStateType::class,
            'admin/roro/surveys/edit-is-active.html.twig'
        );
    }

    protected function handlePostAndGenerateResponse(Survey $survey, Request $request, string $formClass, string $template): Response
    {
        $form = $this->createForm($formClass, $survey);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                $cancel = $form->get('cancel');

                if ($cancel instanceof SubmitButton && $cancel->isClicked()) {
                    return $this->redirectResponseToSurveyView($survey);
                }

                if ($form->isValid()) {
                    $this->entityManager->flush();
                    return $this->redirectResponseToSurveyView($survey);
                }
            }
        }

        return $this->render($template, [
            'form' => $form,
            'survey' => $survey,
        ]);
    }

    protected function redirectResponseToSurveyView(Survey $survey): RedirectResponse
    {
        $url = $this->generateUrl('admin_roro_surveys_view', ['surveyId' => $survey->getId()]);
        return new RedirectResponse($url);
    }
}
