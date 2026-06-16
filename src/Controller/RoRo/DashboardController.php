<?php

namespace App\Controller\RoRo;

use App\Attribute\MaintenanceWarningBanner;
use App\Entity\RoRo\Operator;
use App\Repository\RoRo\SurveyRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Attribute\Route;

#[Route("/roro/operator/{operatorId}")]
#[IsGranted("CAN_VIEW_RORO_DASHBOARD", "operator")]
class DashboardController extends AbstractController
{
    public function __construct(protected SurveyRepository $surveyRepository)
    {}

    #[Route(name: "app_roro_dashboard")]
    #[MaintenanceWarningBanner]
    public function dashboard(
        #[MapEntity(expr: "repository.find(operatorId)")]
        Operator $operator
    ): Response
    {
        return $this->renderTemplate($operator, 'roro/dashboard.html.twig');
    }

    #[Route("/completed-surveys", name: "app_roro_completed_surveys")]
    public function completedSurveys(
        #[MapEntity(expr: "repository.find(operatorId)")]
        Operator $operator
    ): Response
    {
        return $this->renderTemplate($operator, 'roro/completed_surveys.html.twig', [
            'breadcrumbToggle' => 'completed-surveys',
        ]);
    }

    public function renderTemplate(
        #[MapEntity(expr: "repository.find(operatorId)")]
        Operator $operator,
        string $template,
        array $extraParameters = []
    ): Response
    {
        return $this->render($template, array_merge($extraParameters, [
            'operator' => $operator,
            'surveys' => $this->surveyRepository->getSurveysForOperator($operator),
        ]));
    }
}
