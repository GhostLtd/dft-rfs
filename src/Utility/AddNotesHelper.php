<?php

namespace App\Utility;

use App\Entity\SurveyStateInterface;
use App\Form\Admin\NoteType;
use App\Security\Voter\AdminSurveyVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\RouterInterface;

class AddNotesHelper
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected FormFactoryInterface $formFactory,
        protected RouterInterface $router,
        protected Security $security,
    ) {}

    public function formSubmittedAndProcessed(Request $request, SurveyStateInterface $survey, FormInterface $form): bool
    {
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (!$this->security->isGranted(AdminSurveyVoter::EDIT_NOTES, $survey)) {
                throw new AccessDeniedHttpException();
            }

            $add = $form->get('submit');
            $addButtonWasClicked = $add instanceof SubmitButton && $add->isClicked();

            if ($addButtonWasClicked && $form->isValid())
            {
                $survey->addNote($note = $form->getData());
                $this->entityManager->persist($note);
                $this->entityManager->flush();

                return true;
            }
        }

        return false;
    }

    public function addNotesTabAnchor(string $url): string
    {
        return $url . '#tab-notes';
    }

    public function getForm(string $surveyNoteClass): FormInterface
    {
        return $this->formFactory->create(NoteType::class, null, [
            'data_class' => $surveyNoteClass,
        ]);
    }
}