<?php

namespace App\Controller\Admin\International;

use App\Entity\International\Trip;
use App\Entity\International\Vehicle;
use App\Form\Admin\InternationalSurvey\TripType;
use App\Security\Voter\AdminSurveyVoter;
use App\Utility\ConfirmAction\International\DeleteTripConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/irhs')]
class TripController extends AbstractController
{
    private const string ROUTE_PREFIX = "admin_international_trip_";

    public const ADD_ROUTE = self::ROUTE_PREFIX . "add";
    public const DELETE_ROUTE = self::ROUTE_PREFIX . "delete";
    public const EDIT_ROUTE = self::ROUTE_PREFIX . "edit";

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected RequestStack           $requestStack
    )
    {
    }

    #[Route(path: '/vehicle/{vehicleId}/add-trip', name: self::ADD_ROUTE)]
    public function add(
        #[MapEntity(expr: "repository.find(vehicleId)")]
        Vehicle $vehicle
    ): Response
    {
        $this->denyAccessUnlessGranted(AdminSurveyVoter::EDIT, $vehicle->getSurveyResponse()->getSurvey());

        $trip = (new Trip())->setVehicle($vehicle);
        $this->entityManager->persist($trip);

        return $this->handleRequest($trip, 'admin/international/trip/add.html.twig');
    }

    #[Route(path: '/trip/{tripId}/edit', name: self::EDIT_ROUTE)]
    public function edit(
        #[MapEntity(expr: "repository.find(tripId)")]
        Trip $trip
    ): Response
    {
        $this->denyAccessUnlessGranted(AdminSurveyVoter::EDIT, $trip->getVehicle()->getSurveyResponse()->getSurvey());
        return $this->handleRequest($trip, 'admin/international/trip/edit.html.twig');
    }

    protected function handleRequest(Trip $trip, string $template, array $formOptions = []): Response
    {
        $form = $this->createForm(TripType::class, $trip, $formOptions);
        $request = $this->requestStack->getCurrentRequest();

        $unmodifiedTrip = clone $trip;

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);
            $redirectResponse = new RedirectResponse(
                $this->generateUrl(SurveyController::VIEW_ROUTE, ['surveyId' => $trip->getVehicle()->getSurveyResponse()->getSurvey()->getId()]) .
                "#{$trip->getId()}"
            );

            $cancel = $form->get('cancel');
            if ($cancel instanceof SubmitButton && $cancel->isClicked()) {
                return $redirectResponse;
            };

            if ($form->isValid()) {
                $this->entityManager->flush();
                return $redirectResponse;
            }
        }

        return $this->render($template, [
            'trip' => $unmodifiedTrip,
            'form' => $form,
        ]);
    }

    #[Route(path: '/trip/{tripId}/delete', name: self::DELETE_ROUTE)]
    #[Template('admin/international/trip/delete.html.twig')]
    public function delete(
        #[MapEntity(expr: "repository.find(tripId)")]
        Trip                    $trip,
        DeleteTripConfirmAction $deleteTripConfirmAction,
        Request                 $request
    ): RedirectResponse|array
    {
        $survey = $trip->getVehicle()->getSurveyResponse()->getSurvey();
        $this->denyAccessUnlessGranted(AdminSurveyVoter::EDIT, $survey);

        $deleteTripConfirmAction->setSubject($trip);
        return $deleteTripConfirmAction->controller(
            $request,
            fn() => $this->generateUrl(
                SurveyController::VIEW_ROUTE,
                ['surveyId' => $survey->getId()]
            )
        );
    }
}
