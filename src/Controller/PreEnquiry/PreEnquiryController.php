<?php

namespace App\Controller\PreEnquiry;

use App\Entity\PasscodeUser;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Form\PreEnquiry\SubmitPreEnquiryType;
use App\Security\Voter\SurveyVoter;
use App\Workflow\PreEnquiry\PreEnquiryState;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class PreEnquiryController extends AbstractController
{
    public const WIZARD_ROUTE = 'app_pre_enquiry_wizard';
    public const SUMMARY_ROUTE = 'app_pre_enquiry_summary';
    public const COMPLETED_ROUTE = 'app_pre_enquiry_completed';

    /**
     * @Route("/pre-enquiry/completed", name=self::COMPLETED_ROUTE)
     */
    public function completed(UserInterface $user): Response {
        $preEnquiry = $this->getPreEnquiry($user);

        if (!$this->isGranted(SurveyVoter::VIEW_SUBMISSION_SUMMARY, $preEnquiry)) {
            return new RedirectResponse($this->generateUrl(self::SUMMARY_ROUTE));
        }

        return $this->render('pre_enquiry/thanks.html.twig', [
            'preEnquiry' => $preEnquiry,
        ]);
    }

    /**
     * @Route("/pre-enquiry", name=PreEnquiryController::SUMMARY_ROUTE)
     */
    public function index(Request $request, UserInterface $user, EntityManagerInterface $entityManager, WorkflowInterface $preEnquiryStateMachine): Response
    {
        $preEnquiry = $this->getPreEnquiry($user);

        if ($this->isGranted(SurveyVoter::VIEW_SUBMISSION_SUMMARY, $preEnquiry)) {
            return $this->redirectToRoute(self::COMPLETED_ROUTE);
        }
        $this->denyAccessUnlessGranted(SurveyVoter::EDIT, $preEnquiry);

        if (!$preEnquiry->getResponse()) {
            return $this->redirectToRoute(self::WIZARD_ROUTE, ['state' => PreEnquiryState::STATE_INTRODUCTION]);
        }

        $form = $this->createForm(SubmitPreEnquiryType::class);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            $button = $form->get('submit');
            if ($button instanceof SubmitButton && $button->isClicked()) {
                if ($preEnquiryStateMachine->can($preEnquiry, 'complete')) {
                    $preEnquiryStateMachine->apply($preEnquiry, 'complete');
                    $entityManager->flush();
                }

                return $this->redirectToRoute(self::SUMMARY_ROUTE);
            }
        }

        return $this->render('pre_enquiry/summary.html.twig', [
            'preEnquiry' => $preEnquiry,
            'form' => $form->createView(),
        ]);
    }

    protected function getPreEnquiry(UserInterface $user): PreEnquiry
    {
        $preEnquiry = ($user instanceof PasscodeUser) ? $user->getPreEnquiry() : null;

        if (!$preEnquiry || !$preEnquiry instanceof PreEnquiry) {
            throw new AccessDeniedHttpException('No such pre-enquiry');
        }

        return $preEnquiry;
    }

}
