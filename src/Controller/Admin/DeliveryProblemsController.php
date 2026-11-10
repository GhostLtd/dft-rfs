<?php

namespace App\Controller\Admin;

use App\Entity\SurveyNotificationsInterface;
use App\Utility\Notification\DeliveryProblems;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class DeliveryProblemsController extends AbstractController
{
    public const string MARK_SEEN_ROUTE = 'admin_delivery_problems_mark_seen';

    #[Route(path: '/delivery-problems/{type}/{surveyId}/mark-seen', name: self::MARK_SEEN_ROUTE, requirements: ['type' => 'csrgt|irhs|pre-enquiry'], methods: ['POST'])]
    public function markSeen(
        Request                $request,
        string                 $type,
        string                 $surveyId,
        DeliveryProblems       $deliveryProblems,
        EntityManagerInterface $entityManager,
        ClockInterface         $clock,
    ): RedirectResponse
    {
        [$surveyClass, $viewRoute, $idParameter] = DeliveryProblems::SURVEY_TYPES[$type];
        $survey = $entityManager->find($surveyClass, $surveyId);

        if (!$survey instanceof SurveyNotificationsInterface) {
            throw new NotFoundHttpException();
        }

        if ($this->isCsrfTokenValid("delivery-problems-seen-{$survey->getId()}", (string) $request->request->get('_token'))) {
            $deliveryProblems->markSeen($survey, $request->request->get('seen') === '1', \DateTime::createFromImmutable($clock->now()));
            $entityManager->flush();
        }

        return new RedirectResponse($this->generateUrl($viewRoute, [$idParameter => $survey->getId()]).'#tab-invitation-details');
    }
}
