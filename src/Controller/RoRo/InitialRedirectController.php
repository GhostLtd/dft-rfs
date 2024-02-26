<?php

namespace App\Controller\RoRo;

use App\Entity\RoRoUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route("/roro")]
class InitialRedirectController extends AbstractController
{
    public function __invoke(UserInterface $user): RedirectResponse
    {
        if (!$user instanceof RoRoUser) {
            throw new AccessDeniedHttpException('Wrong user type');
        }

        return $this->redirectToRoute('app_roro_dashboard', ['operatorId' => $user->getOperator()->getId()]);
    }
}
