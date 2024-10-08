<?php

namespace App\Controller;

use App\Entity\International\Action;
use App\Entity\International\Trip;
use App\Form\ConfirmationType;
use App\Utility\ReorderUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractActionReorderController extends AbstractController
{
    public function __construct(protected EntityManagerInterface $entityManager)
    {
    }

    abstract protected function getRedirectResponse(Trip $trip): RedirectResponse;
    abstract protected function getTemplate(): string;

    public function reorder(Request $request, Trip $trip): Response
    {
        /** @var Action[] $actions */
        $actions = $trip->getActions()->toArray();

        $mappingParam = $request->query->get('mapping', null);

        /** @var Action[] $sortedActions */
        $sortedActions = ReorderUtils::getSortedItems($actions, $mappingParam);

        $mapping = array_map(fn(Action $action) => $action->getNumber(), $sortedActions);

        foreach($mapping as $i => $newPosition) {
            $actions[$newPosition - 1]->setNumber($i + 1);
        }

        $unloadedBeforeLoaded = [];
        foreach($sortedActions as $action) {
            if (!$action->getLoading()) {
                if ($action->getLoadingAction()->getNumber() > $action->getNumber()) {
                    $unloadedBeforeLoaded[] = $action->getNumber();
                }
            }
        }

        $form = $this->createForm(ConfirmationType::class, null, [
            'yes_label' => 'international.action.re-order.save',
            'no_label' => 'common.actions.cancel',
            'yes_disabled' => !empty($unloadedBeforeLoaded),
        ]);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                $cancel = $form->get('no');
                if ($cancel instanceof SubmitButton && $cancel->isClicked()) {
                    return $this->getRedirectResponse($trip);
                }

                if ($form->isValid() && empty($unloadedBeforeLoaded)) {
                    $this->entityManager->flush();
                    return $this->getRedirectResponse($trip);
                }
            }
        }

        return $this->render($this->getTemplate(), [
            'mapping' => $mapping,
            'trip' => $trip,
            'sortedActions' => $sortedActions,
            'form' => $form,
            'unloadedBeforeLoaded' => $unloadedBeforeLoaded,
        ]);
    }
}