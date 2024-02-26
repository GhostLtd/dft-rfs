<?php

namespace App\Controller\RoRo;

use App\Entity\RoRoUser;
use App\Utility\RoRo\OperatorSwitchHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/roro/switch-operator", name: "app_roro_switch_operator")]
#[IsGranted("CAN_SWITCH_OPERATOR")]
class OperatorSwitchController extends AbstractController
{
    public function __invoke(
        OperatorSwitchHelper $operatorSwitchHelper,
        UserInterface $user,
    ): Response
    {
        if (!$user instanceof RoRoUser) {
            throw new AccessDeniedHttpException();
        }

        return $this->render('roro/switch-operator.html.twig', [
            'operator' => $user->getOperator(),
            'targetOperators' => $operatorSwitchHelper->getOperatorSwitchTargets($user->getOperator()),
        ]);
    }
}
