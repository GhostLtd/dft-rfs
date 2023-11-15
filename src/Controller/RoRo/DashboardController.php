<?php

namespace App\Controller\RoRo;

use App\Entity\RoRo\Operator;
use App\Repository\MaintenanceWarningRepository;
use App\Repository\RoRo\SurveyRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/roro/operator/{operatorId}")]
#[Entity("operator", expr: "repository.find(operatorId)")]
#[Security("is_granted('CAN_VIEW_RORO_DASHBOARD', operator)")]
class DashboardController extends AbstractController
{
    public function __construct(protected SurveyRepository $surveyRepository)
    {}

    #[Route(name: "app_roro_dashboard")]
    public function dashboard(MaintenanceWarningRepository $maintenanceWarningRepository, Operator $operator): Response
    {
        return $this->renderTemplate($operator, 'roro/dashboard.html.twig', [
            'maintenanceWarningBanner' => $maintenanceWarningRepository->getNotificationBanner(),
        ]);
    }

    #[Route("/completed-surveys", name: "app_roro_completed_surveys")]
    public function completedSurveys(Operator $operator): Response
    {
        return $this->renderTemplate($operator, 'roro/completed_surveys.html.twig', [
            'breadcrumbToggle' => 'completed-surveys',
        ]);
    }

    public function renderTemplate(Operator $operator, string $template, array $extraParameters = []): Response
    {
        return $this->render($template, array_merge($extraParameters, [
            'operator' => $operator,
            'surveys' => $this->surveyRepository->getSurveysForOperator($operator),
        ]));
    }
}
