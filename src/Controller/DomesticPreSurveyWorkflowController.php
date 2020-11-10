<?php

namespace App\Controller;

use App\Entity\DomesticSurvey;
use App\Entity\DomesticSurveyResponse;
use App\Workflow\DomesticSurveyState;
use App\Workflow\FormWizardInterface;
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
    public const SESSION_KEY = 'wizard.domestic_pre_survey_state';

    /**
     * @param Request $request
     * @return FormWizardInterface
     */
    protected function getFormWizard(Request $request)
    {
        /** @var FormWizardInterface $formWizard */
        $formWizard = $request->getSession()->get(self::SESSION_KEY, new DomesticSurveyState());
        if (is_null($formWizard->getSubject())) {
            $surveyResponses = $this->getDoctrine()->getRepository(DomesticSurveyResponse::class)->findAll();
            $formWizard->setSubject(array_pop($surveyResponses));
        }
        $formWizard->setSubject($this->getDoctrine()->getManager()->find(DomesticSurveyResponse::class, $formWizard->getSubject()->getId()));
        return $formWizard;
    }

    /**
     * @Route("/domestic-survey/pre-survey/{state}")
     * @Route("/domestic-survey/pre-survey", name="app_domesticpresurveyworkflow_start")
     * @param WorkflowInterface $domesticPreSurveyStateMachine
     * @param Request $request
     * @param $state
     * @return Response
     */
    public function index(WorkflowInterface $domesticPreSurveyStateMachine, Request $request, $state = null): Response
    {
        $formWizard = $this->getFormWizard($request);

        if (is_null($state)) return $this->redirectToRoute('app_domesticpresurveyworkflow_index', ['state' => $formWizard->getState()]);

        if ($state !== $formWizard->getState()) {
            if ($formWizard->isValidJumpInState($state))
            {
                $formWizard->setState($state);
            } else {
                return $this->redirectToRoute('app_domesticpresurveyworkflow_index', ['state' => $formWizard->getState()]);
            }
        }

        /** @var FormInterface $form */
        list($form, $template) = $this->getFormAndTemplate($formWizard);

        $form->handleRequest($request);

        // validate/state-transition
        if (!is_null($form->getClickedButton()))
        {
            if ($form->isValid())
            {
                $transitions = $domesticPreSurveyStateMachine->getEnabledTransitions($formWizard);

                foreach ($transitions as $k=>$v)
                {
                    if ($transitionWhenFormData = $domesticPreSurveyStateMachine->getMetadataStore()->getTransitionMetadata($v)['transitionWhenFormData'] ?? false)
                    {
                        if ($form->get($transitionWhenFormData['property'])->getData() === $transitionWhenFormData['value'])
                        {
                            return $this->applyTransitionAndRedirect($request, $domesticPreSurveyStateMachine, $formWizard, $v);
                        } else {
                            unset($transitions[$k]);
                        }
                    }
                }
                $transitions = array_values($transitions);

                if (count($transitions) === 1)
                {
                    return $this->applyTransitionAndRedirect($request, $domesticPreSurveyStateMachine, $formWizard, $transitions[0]);
                }
            }
        }

        return $this->render("domestic_pre_survey_workflow/{$template}.html.twig", [
            'form' => $form->createView(),
            'domesticSurvey' => $formWizard->getSubject(),
        ]);
    }

    protected function applyTransitionAndRedirect(Request $request, WorkflowInterface $stateMachine, FormWizardInterface $formWizard, Transition $transition)
    {
        $stateMachine->apply($formWizard, $transition->getName());
        $request->getSession()->set(self::SESSION_KEY, $formWizard);

//        if ($alternativeRoute = $stateMachine->getMetadataStore()->getTransitionMetadata($transition)['persist'] ?? false)
//        {
//            $this->getDoctrine()->getManager()->persist($formWizard->getSubject());
            $this->getDoctrine()->getManager()->flush();
//        }

        if ($alternativeRoute = $stateMachine->getMetadataStore()->getTransitionMetadata($transition)['redirectRoute'] ?? false)
        {
            $request->getSession()->remove(self::SESSION_KEY);
            return $this->redirectToRoute($alternativeRoute);
        }

        return $this->redirectToRoute('app_domesticpresurveyworkflow_index', ['state' => $formWizard->getState()]);
    }

    /**
     * @param FormWizardInterface $formWizard
     * @return array
     */
    protected function getFormAndTemplate(FormWizardInterface $formWizard)
    {
        $state = $formWizard->getState();
        $formMap = $formWizard->getStateFormMap();

        if (isset($formMap[$state]))
        {
            $form = $this->createForm($formMap[$state]);
            if ($form->getConfig()->getDataClass())
            {
                $form->setData($formWizard->getSubject());
            }
        } else {
            $form = $this->createFormBuilder()
                ->getForm();
        }
        $template = $formWizard->getStateTemplateMap()[$state] ?? 'form-step';
        $form->add('continue', ButtonType::class, ['type' => 'submit']);

        return [$form, $template];
    }
}
