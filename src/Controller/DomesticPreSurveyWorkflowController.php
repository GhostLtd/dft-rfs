<?php

namespace App\Controller;

use App\Entity\DomesticSurvey;
use App\Entity\DomesticSurveyResponse;
use App\Form\Domestic\CompletableStatusType;
use App\Form\Domestic\ContactDetailsType;
use App\Form\Domestic\HireeDetailsType;
use App\Form\Domestic\OnHireStatusType;
use App\Form\Domestic\ReasonCantCompleteType;
use App\Form\WorkflowChoiceFormInterface;
use App\Workflow\DomesticSurveyState;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;

class DomesticPreSurveyWorkflowController extends AbstractController
{
    private const SESSION_KEY = 'wizard.domestic_pre_survey_state';
    
    private const FORM_MAP = [
        DomesticSurveyState::STATE_PRE_SURVEY_REQUEST_CONTACT_DETAILS => ContactDetailsType::class,
        DomesticSurveyState::STATE_PRE_SURVEY_ASK_COMPLETABLE => CompletableStatusType::class,
        DomesticSurveyState::STATE_PRE_SURVEY_ASK_ON_HIRE => OnHireStatusType::class,
        DomesticSurveyState::STATE_PRE_SURVEY_ASK_REASON_CANT_COMPLETE => ReasonCantCompleteType::class,
        DomesticSurveyState::STATE_PRE_SURVEY_ASK_HIREE_DETAILS => HireeDetailsType::class,
    ];

    private const TEMPLATE_MAP = [
        DomesticSurveyState::STATE_PRE_SURVEY_INTRODUCTION => 'introduction',
        DomesticSurveyState::STATE_PRE_SURVEY_SUMMARY => 'summary',
        DomesticSurveyState::STATE_PRE_SURVEY_REQUEST_CONTACT_DETAILS => 'form-contact-details',
        DomesticSurveyState::STATE_PRE_SURVEY_ASK_HIREE_DETAILS => 'form-hiree-details',
    ];

    /**
     * @Route("/domestic/pre-survey/wizard/{state}")
     * @Route("/domestic/pre-survey/wizard")
     * @param WorkflowInterface $domesticPreSurveyStateMachine
     * @param Request $request
     * @param $state
     * @return Response
     */
    public function index(WorkflowInterface $domesticPreSurveyStateMachine, Request $request, $state = null): Response
    {
        /** @var DomesticSurveyState $domesticSurveyState */
        $domesticSurveyState = $request->getSession()->get(
            self::SESSION_KEY,
            (new DomesticSurveyState())
        );

        if (is_null($state)) return $this->redirectToRoute('app_domesticpresurveyworkflow_index', ['state' => $domesticSurveyState->getState()]);

        if ($state !== $domesticSurveyState->getState()) {
            if ($domesticSurveyState->isVisitedState($state))
            {
                $domesticSurveyState->setState($state);
            } else {
                $this->redirectToRoute('app_domesticpresurveyworkflow_index', ['state' => $domesticSurveyState->getState()]);
            }
        }
        $domesticSurveyState->setVisitedState($state);

        if (is_null($domesticSurveyState->getSurvey()->getSurveyResponse()))
            $domesticSurveyState->getSurvey()->setSurveyResponse(new DomesticSurveyResponse());

        /** @var FormInterface $form */
        list($form, $template) = $this->getFormAndTemplate($domesticSurveyState);

        $form->handleRequest($request);

        // validate/state-transition
        if (!is_null($form->getClickedButton()))
        {
            if ($form->isValid())
            {
                $transitions = $domesticPreSurveyStateMachine->getEnabledTransitions($domesticSurveyState);

                foreach ($transitions as $k=>$v)
                {
                    if ($transitionWhenFormData = $domesticPreSurveyStateMachine->getMetadataStore()->getTransitionMetadata($v)['transitionWhenFormData'] ?? false)
                    {
                        if ($form->get($transitionWhenFormData['property'])->getData() === $transitionWhenFormData['value'])
                        {
                            return $this->applyTransitionAndRedirect($request, $domesticPreSurveyStateMachine, $domesticSurveyState, $v);
                        } else {
                            unset($transitions[$k]);
                        }
                    }
                }
                $transitions = array_values($transitions);

                if (count($transitions) === 1)
                {
                    return $this->applyTransitionAndRedirect($request, $domesticPreSurveyStateMachine, $domesticSurveyState, $transitions[0]);
                }
            }
        }

        return $this->render("domestic_pre_survey_workflow/{$template}.html.twig", [
            'form' => $form->createView(),
            'domesticSurvey' => $domesticSurveyState->getSurvey(),
        ]);
    }

    protected function applyTransitionAndRedirect(Request $request, WorkflowInterface $stateMachine, $object, Transition $transition)
    {
        $stateMachine->apply($object, $transition->getName());
        $request->getSession()->set(self::SESSION_KEY, $object);
        return $this->redirectToRoute('app_domesticpresurveyworkflow_index', ['state' => $object->getState()]);
    }

    protected function formClassImplements($place, $interfaceClass)
    {
        return in_array(
            $interfaceClass,
            (self::FORM_MAP[$place] ?? false) ? class_implements(self::FORM_MAP[$place]) : []
        );
    }

    /**
     * @param DomesticSurveyState $domesticSurveyState
     * @return array
     */
    protected function getFormAndTemplate(DomesticSurveyState $domesticSurveyState)
    {
        $state = $domesticSurveyState->getState();

        if (isset(self::FORM_MAP[$state]))
        {
            $form = $this->createForm(self::FORM_MAP[$state]);
            if ($form->getConfig()->getDataClass())
            {
                $form->setData($domesticSurveyState->getSurvey()->getSurveyResponse());
            }
        } else {
            $form = $this->createFormBuilder()
                ->getForm();
        }
        $template = self::TEMPLATE_MAP[$state] ?? 'form-step';
        $form->add('continue', ButtonType::class, ['type' => 'submit']);

        return [$form, $template];
    }
}
