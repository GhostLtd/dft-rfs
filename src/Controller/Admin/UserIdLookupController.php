<?php

namespace App\Controller\Admin;

use App\Form\Admin\UserIdLookupType;
use App\Repository\PasscodeUserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserIdLookupController extends AbstractController
{
    #[Route(path: '/user-id-lookup', name: 'admin_user_id_lookup')]
    public function lookup(Request $request, PasscodeUserRepository $userRepository): Response
    {
        $form = $this->createForm(UserIdLookupType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userIdField = $form->get('user_id');

            $userId = intval($userIdField->getData());
            $user = $userRepository->lookupUserByIdentifier($userId);

            if ($user) {
                if ($survey = $user->getDomesticSurvey()) {
                    return $this->redirectToRoute('admin_domestic_survey_view', ['surveyId' => $survey->getId()]);
                } else if ($survey = $user->getInternationalSurvey()) {
                    return $this->redirectToRoute('admin_international_survey_view', ['surveyId' => $survey->getId()]);
                } else if ($survey = $user->getPreEnquiry()) {
                    return $this->redirectToRoute('admin_preenquiry_view', ['preEnquiryId' => $survey->getId()]);
                } else {
                    $userIdField->addError(new FormError('User found, but has no related domestic, international or pre-enquiry survey'));
                }
            } else {
                $userIdField->addError(new FormError('No such user identifier found'));
            }
        }

        return $this->render('admin/user_id_lookup/lookup.html.twig', [
            'form' => $form,
        ]);
    }
}
