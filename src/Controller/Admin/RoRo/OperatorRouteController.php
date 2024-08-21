<?php

namespace App\Controller\Admin\RoRo;

use App\Entity\RoRo\Operator;
use App\Entity\Route\Route as RouteEntity;
use App\Form\Admin\RoRo\OperatorRouteType;
use App\Utility\ConfirmAction\RoRo\Admin\UnassignRouteConfirmAction;
use App\Utility\RoRo\SurveyCreationHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/operators', name: 'admin_operators_')]
class OperatorRouteController extends AbstractController
{
    #[Route(path: '/{operatorId}/route/assign', name: 'assign_route')]
    public function assignRoute(
        EntityManagerInterface $entityManager,
        Request $request,
        SurveyCreationHelper $surveyCreationHelper,
        #[MapEntity(id: "operatorId")] Operator $operator
    ): Response
    {
        $form = $this->createForm(OperatorRouteType::class, null, [
            'operator' => $operator,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $cancelButton = $form->get('cancel');
            $redirectResponse = $this->redirectToRoute('admin_operators_view', ['operatorId' => $operator->getId()]);

            if ($cancelButton instanceof SubmitButton && $cancelButton->isClicked()) {
                return $redirectResponse;
            }

            if ($form->isValid()) {
                $route = $form->get('route')->getData();

                if ($route instanceof RouteEntity && $route->getIsActive()) {
                    $operator->addRoute($route);
                }

                if ($form->get('backdateSurveys')->getData() === true) {
                    $backdateSurveys = $form->get('backdateSurveysGroup');
                    $year = $backdateSurveys->get('year')->getData();
                    $month = $backdateSurveys->get('month')->getData();
                } else {
                    $entityManager->flush();
                    return $redirectResponse;
                }

                try {
                    // N.B. surveyCreationHelper does a flush()
                    $surveyCreationHelper->createSurveysForGivenOperatorAndRouteStartingAt($operator, $route, $year, $month);
                    return $redirectResponse;
                } catch (\Exception $e) {
                    $form
                        ->get('backdateSurveys')
                        ->addError(new FormError($e->getMessage()));
                }
            }
        }

        return $this->render('admin/roro/operators/assign-route.html.twig', [
            'form' => $form,
            'operator' => $operator,
            'translation_parameters' => [
                'code' => $operator->getCode(),
                'name' => $operator->getName(),
            ],
        ]);
    }

    #[Route(path: '/{operatorId}/route/{routeId}/unassign', name: 'unassign_route')]
    public function unassignRoute(
        Request $request,
        UnassignRouteConfirmAction $unassignRouteConfirmAction,
        #[MapEntity(id: "operatorId")] Operator $operator,
        #[MapEntity(id: "routeId")] RouteEntity $route
    ): Response
    {
        $data = $unassignRouteConfirmAction
            ->setSubject([
                'operator' => $operator,
                'route' => $route
            ])
            ->controller(
                $request,
                fn() => $this->generateUrl('admin_operators_view', ['operatorId' => $operator->getId()])
            );

        if ($data instanceof RedirectResponse) {
            return $data;
        }

        return $this->render("admin/roro/operators/unassign-route.html.twig", $data);
    }
}
