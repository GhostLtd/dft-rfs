<?php

namespace App\Controller\RoRo;

use Alphagov\Notifications\Client;
use App\Entity\RoRoUser;
use App\Form\RoRoLoginType;
use App\Repository\MaintenanceWarningRepository;
use App\Repository\RoRoUserRepository;
use App\Utility\AlphagovNotify\Reference;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

#[Route('/roro', name: "app_roro_")]
class LoginController extends AbstractController
{
    #[Route("/login", name: "login")]
    public function login(
        Request $request,
        MaintenanceWarningRepository $maintenanceWarningRepository,
        LoginLinkHandlerInterface $loginLinkHandler,
        Client $client,
        RoRoUserRepository $userRepository
    ): Response {
        $user = $this->getUser();

        if ($user instanceof RoRoUser) {
            return $this->redirectToRoute('app_roro_dashboard');
        }

        $form = $this->createForm(RoRoLoginType::class);
        $form->handleRequest($request);

        $authenticationError = $this->getAuthenticationError($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->getData()['username'];

            $user = $userRepository->findOneBy(['username' => $email]);

            if ($user) {
                $loginLinkDetails = $loginLinkHandler->createLoginLink($user);

                if ($this->getParameter('kernel.environment') === 'dev' &&
                    $this->getParameter('dev.roro.auto_login')
                ) {
                    return new RedirectResponse($loginLinkDetails->getUrl());
                }

                $client->sendEmail($email, Reference::RORO_LOGIN_LINK, [
                    'login_link' => $loginLinkDetails->getUrl(),
                ]);
            }

            return $this->redirectToRoute('app_roro_login_check_email');
        }

        return $this->render('roro/login.html.twig', [
            'form' => $form->createView(),
            'authenticationError' => $authenticationError,
            'maintenanceWarningBanner' => $maintenanceWarningRepository->getNotificationBannerForFrontend(),
        ]);
    }

    #[Route("/check-email", name: "login_check_email")]
    public function loginCheckEmail(Security $security): Response {
        if ($security->getUser() !== null) {
            return $this->redirectToRoute('app_roro_dashboard');
        }

        return $this->render('roro/login_check_email.html.twig');
    }

    #[Route("/authenticate", name: "login_check")]
    public function loginCheck()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the login check on your firewall.');
    }

    #[Route("/logout", name: "logout")]
    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    public function getAuthenticationError(Request $request): ?string
    {
        $session = $request->getSession();
        $authenticationError = $session->get(Security::AUTHENTICATION_ERROR);
        $session->remove(Security::AUTHENTICATION_ERROR);

        return $authenticationError;
    }
}
