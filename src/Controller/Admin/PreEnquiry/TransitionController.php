<?php

namespace App\Controller\Admin\PreEnquiry;

use App\Entity\PreEnquiry\PreEnquiry;
use App\Security\Voter\AdminSurveyVoter;
use App\Utility\ConfirmAction\PreEnquiry\Admin\PreEnquiryWorkflowConfirmAction;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TransitionController extends AbstractController
{
    /**
     * @Route("/pre-enquiry/{preEnquiryId}/transition/{transition}", name=EditController::TRANSITION_ROUTE,
     *     requirements={"transition": "complete|re_open|approve|reject|un_reject|un_approve"}
     * )
     * @Entity("preEnquiry", expr="repository.find(preEnquiryId)")
     * @IsGranted(AdminSurveyVoter::TRANSITION, subject="preEnquiry")
     * @Template("admin/pre_enquiry/workflow-action.html.twig")
     */
    public function complete(PreEnquiryWorkflowConfirmAction $surveyWorkflowConfirmAction, Request $request, PreEnquiry $preEnquiry, $transition)
    {
        $surveyWorkflowConfirmAction
            ->setSubject($preEnquiry)
            ->setTransition($transition);

        return $surveyWorkflowConfirmAction->controller(
            $request,
            function() use ($preEnquiry) {
                return $this->generateUrl(
                    EditController::VIEW_ROUTE,
                    ['preEnquiryId' => $preEnquiry->getId()]
                );
            });
    }
}