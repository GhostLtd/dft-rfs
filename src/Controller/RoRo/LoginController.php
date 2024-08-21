<?php

namespace App\Controller\RoRo;

use App\Entity\RoRoUser;
use App\Features;
use App\Form\RoRoLoginType;
use App\Messenger\AlphagovNotify\RoRoLoginEmail;
use App\Repository\MaintenanceWarningRepository;
use App\Repository\RoRoUserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RateLimiter\RequestRateLimiterInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RateLimiter\Exception\RateLimitExceededException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/roro', name: "app_roro_")]
class LoginController extends AbstractController
{
    #[Route("/login", name: "login")]
    public function login(
        Features $features,
        LoginLinkHandlerInterface $loginLinkHandler,
        LoggerInterface $logger,
        MaintenanceWarningRepository $maintenanceWarningRepository,
        MessageBusInterface $messageBus,
        Request $request,
        RequestRateLimiterInterface $roroLoginLimiter,
        RoRoUserRepository $userRepository,
        TranslatorInterface $translator,
    ): Response {
        $user = $this->getUser();

        if ($user instanceof RoRoUser) {
            return $this->redirectToRoute('app_roro_dashboard', ['operatorId' => $user->getOperator()->getId()]);
        }

        $form = $this->createForm(RoRoLoginType::class);
        $form->handleRequest($request);

        $authenticationError = $this->getAuthenticationError($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->getData()['username'];

            try {
                $user = $userRepository->findOneBy(['username' => $email]);

                if ($user) {
                    $roroLoginLimiter->consume($request)->ensureAccepted();
                    $loginLinkDetails = $loginLinkHandler->createLoginLink($user);

                    if ($this->getParameter('kernel.environment') === 'dev' &&
                        $features->isEnabled(Features::DEV_RORO_AUTO_LOGIN)
                    ) {
                        $logger->info("RoRo Login submitted: {$email} - success - DEV mode auto-redirect");
                        return new RedirectResponse($loginLinkDetails->getUrl());
                    }

                    $logger->info("RoRo Login submitted: {$email} - check-email page, message dispatched");

                    $messageBus->dispatch(
                        new RoRoLoginEmail($email, ['login_link' => $loginLinkDetails->getUrl()])
                    );
                } else {
                    $logger->info("RoRo Login submitted: {$email} - check-email page, no such user");
                }

                return $this->redirectToRoute('app_roro_login_check_email');
            }
            catch(RateLimitExceededException) {
                $authenticationError = new AuthenticationException($translator->trans('roro.auth.rate-limit', [
                    // 'retry_after' => $exceededException->getRateLimit()->getRetryAfter(),
                ]));
                $logger->info("RoRo Login submitted: {$email} - failure - rate-limit hit");
            }
        }

        return $this->render('roro/login.html.twig', [
            'form' => $form,
            'authenticationError' => $authenticationError,
            'maintenanceWarningBanner' => $maintenanceWarningRepository->getNotificationBanner(),
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
    public function loginCheck(Request $request): Response
    {
        $expires = $request->query->get('expires');
        $username = $request->query->get('user');
        $hash = $request->query->get('hash');

        if (!$expires || !$username || !$hash) {
            return $this->redirectToRoute('app_roro_login');
        }

        return $this->render('roro/login_process.html.twig', [
            'expires' => $expires,
            'user' => $username,
            'hash' => $hash,
        ]);
    }

    #[Route("/logout", name: "logout")]
    public function logout(): never
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    public function getAuthenticationError(Request $request): ?AuthenticationException
    {
        $session = $request->getSession();
        $authenticationError = $session->get(SecurityRequestAttributes::AUTHENTICATION_ERROR);
        $session->remove(SecurityRequestAttributes::AUTHENTICATION_ERROR);

        return $authenticationError;
    }
}
