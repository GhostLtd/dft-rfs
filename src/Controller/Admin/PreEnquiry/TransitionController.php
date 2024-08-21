<?php

namespace App\Controller\Admin\PreEnquiry;

use App\Entity\PreEnquiry\PreEnquiry;
use App\Security\Voter\AdminSurveyVoter;
use App\Utility\ConfirmAction\PreEnquiry\Admin\PreEnquiryWorkflowConfirmAction;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class TransitionController extends AbstractController
{
    #[Route(path: '/pre-enquiry/{preEnquiryId}/transition/{transition}', name: EditController::TRANSITION_ROUTE, requirements: ['transition' => 'complete|re_open|reject|un_reject'])]
    #[IsGranted(AdminSurveyVoter::TRANSITION, subject: 'preEnquiry')]
    #[Template('admin/pre_enquiry/workflow-action.html.twig')]
    public function complete(
        PreEnquiryWorkflowConfirmAction $surveyWorkflowConfirmAction,
        Request                         $request,
        #[MapEntity(expr: "repository.find(preEnquiryId)")]
        PreEnquiry                      $preEnquiry,
        string                          $transition
    ): RedirectResponse|array
    {
        $surveyWorkflowConfirmAction
            ->setSubject($preEnquiry)
            ->setTransition($transition);

        return $surveyWorkflowConfirmAction->controller(
            $request,
            fn() => $this->generateUrl(
                EditController::VIEW_ROUTE,
                ['preEnquiryId' => $preEnquiry->getId()]
            ));
    }
}
