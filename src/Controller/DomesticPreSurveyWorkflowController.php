<?php

namespace App\Controller;

use App\Entity\DomesticSurvey;
use App\Form\Domestic\CompletableStatusType;
use App\Form\Domestic\ContactDetailsType;
use App\Form\Domestic\HireeDetailsType;
use App\Form\Domestic\OnHireStatusType;
use App\Form\Domestic\ReasonCantCompleteType;
use App\Form\WorkflowChoiceFormInterface;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\WorkflowInterface;

class DomesticPreSurveyWorkflowController extends AbstractController
{
    private const SESSION_KEY = 'wizard.domestic_pre_survey';

    private const FORM_MAP = [
        DomesticSurvey::STATE_PRE_SURVEY_REQUEST_CONTACT_DETAILS => ContactDetailsType::class,
        DomesticSurvey::STATE_PRE_SURVEY_ASK_COMPLETABLE => CompletableStatusType::class,
        DomesticSurvey::STATE_PRE_SURVEY_ASK_ON_HIRE => OnHireStatusType::class,
        DomesticSurvey::STATE_PRE_SURVEY_ASK_REASON_CANT_COMPLETE => ReasonCantCompleteType::class,
        DomesticSurvey::STATE_PRE_SURVEY_ASK_HIREE_DETAILS => HireeDetailsType::class,
    ];

    private const TEMPLATE_MAP = [
        DomesticSurvey::STATE_PRE_SURVEY_INTRODUCTION => 'introduction',
        DomesticSurvey::STATE_PRE_SURVEY_SUMMARY => 'summary',
        DomesticSurvey::STATE_PRE_SURVEY_REQUEST_CONTACT_DETAILS => 'form-contact-details',
    ];

    /**
     * @Route("/domestic/pre-survey/wizard")
     * @Route("/domestic/pre-survey/wizard/{state}")
     * @param WorkflowInterface $domesticPreSurveyStateMachine
     * @param Request $request
     * @return Response
     */
    public function index(WorkflowInterface $domesticPreSurveyStateMachine, Request $request): Response
    {
        /** @var DomesticSurvey $domesticSurvey */
        $domesticSurvey = $request->getSession()->get(self::SESSION_KEY, new DomesticSurvey());
        $marking = $domesticPreSurveyStateMachine->getMarking($domesticSurvey);
        $place = array_keys($marking->getPlaces())[0];

        if (isset(self::FORM_MAP[$place]))
        {
            $form = $this->createForm(self::FORM_MAP[$place]);
            if ($form->getConfig()->getDataClass())
            {
                $form->setData($domesticSurvey->getSurveyResponse());
            }
        } else {
            $form = $this->createFormBuilder()
                ->getForm();
        }
        $template = self::TEMPLATE_MAP[$place] ?? 'form-step';
        $form->add('continue', ButtonType::class, ['type' => 'submit']);

        // if post, handle form
        if ($request->getMethod() === Request::METHOD_POST)
        {
            $form->handleRequest($request);
            $clickedButton = $form->getClickedButton();

            // validate/state-transition
            if ($clickedButton->getName() === 'continue')
            {
                if ($form->isValid())
                {
                    $transitions = $domesticPreSurveyStateMachine->getEnabledTransitions($domesticSurvey);

                    if (in_array(WorkflowChoiceFormInterface::class, class_implements(self::FORM_MAP[$place])))
                    {
                        $choiceFormResult = array_values($form->all())[0]->getData();

                        foreach ($transitions as $k=>$v)
                        {
                            $metadataResult = $domesticPreSurveyStateMachine->getMetadataStore()->getTransitionMetadata($v)['result'] ?? null;
                            if ($metadataResult !== $choiceFormResult) {
                                unset ($transitions[$k]);
                            }
                        }
                        $transitions = array_values($transitions);
                    }

                    if (count($transitions) === 1)
                    {
                        $domesticPreSurveyStateMachine->apply($domesticSurvey, $transitions[0]->getName());
                        $newPlace = array_keys($domesticPreSurveyStateMachine->getMarking($domesticSurvey)->getPlaces())[0];

                        // save to session
                        $request->getSession()->set(self::SESSION_KEY, $domesticSurvey);

                        // redirect
                        return $this->redirectToRoute('app_domesticpresurveyworkflow_index_1', ['state' => $newPlace]);
                    }
                }
            }
        }

        return $this->render("domestic_pre_survey_workflow/{$template}.html.twig", [
            'form' => $form->createView(),
            'domesticSurvey' => $domesticSurvey,
        ]);
    }
}
