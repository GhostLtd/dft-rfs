<?php

namespace App\Controller;

use App\Attribute\MaintenanceWarningBanner;
use App\Form\PasscodeLoginType;
use App\Utility\LoginLinkToken;
use App\Utility\PasscodeFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

class PasscodeSecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    #[MaintenanceWarningBanner]
    public function login(AuthenticationUtils $authenticationUtils, TranslatorInterface $translator, Request $request): Response
    {
        // A short login link (/c/{token}) prefills code 1 only; an invalid token just gives the empty form.
        $code1 = LoginLinkToken::decode($request->query->getString('c'));
        $form = $this->createForm(PasscodeLoginType::class, $code1 === null ? null : ['0' => PasscodeFormatter::formatPasscode($code1)]);

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        if ($error) {
            $form->get('passcode')->addError(new FormError($translator->trans($error->getMessageKey(), $error->getMessageData(), 'security')));
        }

        return $this->render('security/login.html.twig', [
            'form' => $form,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): never
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
