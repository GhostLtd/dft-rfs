<?php

namespace App\Controller\InternationalSurvey;

use App\Repository\International\ActionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class ActionController extends AbstractController
{
    use SurveyHelperTrait;

    public const VIEW_ROUTE = 'app_internationalsurvey_action_view';

    protected $actionRepository;

    public function __construct(ActionRepository $actionRepository)
    {
        $this->actionRepository = $actionRepository;
    }

    /**
     * @Route("/international-survey/actions/{actionId}", name=self::VIEW_ROUTE)
     */
    public function view(UserInterface $user, string $actionId) {
        $response = $this->getSurveyResponse($user);

        if (!$response) {
            throw new AccessDeniedHttpException();
        }

        $action = $this->actionRepository->findOneByIdAndSurveyResponse($actionId, $response);

        if (!$action) {
            throw new NotFoundHttpException();
        }

        return $this->render('international_survey/action/summary.html.twig', [
            'action' => $action,
        ]);
    }
}