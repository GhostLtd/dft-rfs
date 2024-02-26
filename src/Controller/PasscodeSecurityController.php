<?php

namespace App\Controller;

use App\Form\PasscodeLoginType;
use App\Repository\MaintenanceWarningRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

class PasscodeSecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, TranslatorInterface $translator, MaintenanceWarningRepository $maintenanceWarningRepository): Response
    {
        $form = $this->createForm(PasscodeLoginType::class);

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        if ($error) {
            $form->get('passcode')->addError(new FormError($translator->trans($error->getMessageKey(), [], 'security')));
        }

        return $this->render('security/login.html.twig', [
            'form' => $form,
            'error' => $error,
            'maintenanceWarningBanner' => $maintenanceWarningRepository->getNotificationBanner(),
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): never
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
