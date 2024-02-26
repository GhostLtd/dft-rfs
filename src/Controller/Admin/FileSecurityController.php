<?php

namespace App\Controller\Admin;

use App\Form\AdminLoginType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class FileSecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'admin_login')]
    public function login(AuthenticationUtils $authenticationUtils, Session $session): Response
    {
        if ($this->isGranted('ROLE_ADMIN_USER')) {
            return $this->redirectToRoute('admin_index');
        }

        $form = $this->createForm(AdminLoginType::class);

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        if ($error) {
            $form->addError(new FormError($error->getMessageKey()));
        }

        return $this->render('admin/security/login.html.twig', [
            'form' => $form,
            'error' => $error
        ]);
    }

    #[Route(path: '/logout', name: 'admin_logout')]
    public function logout(): never
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
