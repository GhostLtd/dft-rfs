<?php

namespace App\Controller\RoRo;

use App\Entity\RoRoUser;
use App\Repository\MaintenanceWarningRepository;
use App\Repository\RoRo\SurveyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route("/roro")]
class DashboardController extends AbstractController
{
    public function __construct(protected SurveyRepository $surveyRepository)
    {}


    #[Route(name: "app_roro_dashboard")]
    public function dashboard(MaintenanceWarningRepository $maintenanceWarningRepository, UserInterface $user): Response
    {
        return $this->renderTemplate($user, 'roro/dashboard.html.twig', [
            'maintenanceWarningBanner' => $maintenanceWarningRepository->getNotificationBanner(),
        ]);
    }

    #[Route("/completed-surveys", name: "app_roro_completed_surveys")]
    public function completedSurveys(UserInterface $user): Response
    {
        return $this->renderTemplate($user, 'roro/completed_surveys.html.twig', [
            'breadcrumbToggle' => 'completed-surveys',
        ]);
    }

    public function renderTemplate(UserInterface $user, string $template, array $extraParameters = []): Response
    {
        if (!$user instanceof RoRoUser) {
            throw new NotFoundHttpException();
        }

        return $this->render($template, array_merge($extraParameters, [
            'hasBookmarks' => $user->getBookmarkedRoutes()->count() >= 1,
            'surveys' => $this->surveyRepository->getSurveysForUser($user),
            'user' => $user,
        ]));
    }
}
