<?php

namespace App\Controller\InternationalSurvey;

use App\Entity\International\Action;
use App\Entity\Utility;
use App\Form\ConfirmationType;
use App\Repository\International\ActionRepository;
use App\Repository\International\TripRepository;
use App\Utility\ConfirmAction\International\DeleteActionConfirmAction;
use App\Utility\ReorderUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(new Expression("is_granted('EDIT', user.getInternationalSurvey())"))]
#[Route(path: '/international-survey')]
class ActionController extends AbstractController
{
    use SurveyHelperTrait;

    #[Route(path: '/trips/{tripId}/add-another-action', name: 'app_internationalsurvey_action_add_another', requirements: ['tripId' => Utility::UUID_REGEX])]
    public function addAnother(string $tripId): Response
    {
        return $this->redirectToRoute('app_internationalsurvey_action_add_start', ['tripId' => $tripId]);
    }

    #[Route(path: '/actions/{actionId}', name: 'app_internationalsurvey_action_view', requirements: ['actionId' => Utility::UUID_REGEX])]
    public function view(
        ActionRepository $actionRepository,
        string           $actionId
    ): Response
    {
        $action = $actionRepository->findOneByIdAndSurveyResponse($actionId, $this->getSurveyResponse());

        if (!$action) {
            throw new NotFoundHttpException();
        }

        return $this->render('international_survey/action/view.html.twig', [
            'action' => $action,
        ]);
    }

    #[Route(path: '/actions/{actionId}/delete', name: 'app_internationalsurvey_action_delete', requirements: ['actionId' => Utility::UUID_REGEX])]
    #[Template('international_survey/action/delete.html.twig')]
    public function delete(
        ActionRepository          $actionRepository,
        DeleteActionConfirmAction $confirmAction,
        Request                   $request,
        string                    $actionId,
    ): RedirectResponse|array
    {
        $action = $actionRepository->findOneByIdAndSurveyResponse($actionId, $this->getSurveyResponse());

        if (!$action) {
            throw new NotFoundHttpException();
        }

        $tripId = $action->getTrip()->getId();

        return $confirmAction
            ->setSubject($action)
            ->setExtraViewData([
                'action' => $action,
            ])
            ->controller(
                $request,
                fn() => $this->generateUrl(TripController::TRIP_ROUTE, ['id' => $tripId]),
                fn() => $this->generateUrl('app_internationalsurvey_action_view', ['actionId' => $actionId]),
            );
    }

    #[Route(path: '/trips/{tripId}/reorder-actions', name: 'app_internationalsurvey_action_reorder')]
    public function reorder(
        EntityManagerInterface $entityManager,
        Request                $request,
        TripRepository         $tripRepository,
        string                 $tripId,
    ): Response
    {
        $trip = $tripRepository->findByIdAndSurveyResponse($tripId, $this->getSurveyResponse());

        if (!$trip) {
            throw new NotFoundHttpException();
        }

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

            $redirectResponse = $this->redirectToRoute(TripController::TRIP_ROUTE, ['id' => $tripId]);
            if ($form->isSubmitted()) {
                $cancel = $form->get('no');
                if ($cancel instanceof SubmitButton && $cancel->isClicked()) {
                    return $redirectResponse;
                }

                if ($form->isValid() && empty($unloadedBeforeLoaded)) {
                    $entityManager->flush();
                    return $redirectResponse;
                }
            }
        }

        return $this->render('international_survey/action/re-order.html.twig', [
            'mapping' => $mapping,
            'trip' => $trip,
            'sortedActions' => $sortedActions,
            'form' => $form,
            'unloadedBeforeLoaded' => $unloadedBeforeLoaded,
        ]);
    }
}
