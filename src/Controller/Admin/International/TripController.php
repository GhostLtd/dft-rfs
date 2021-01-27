<?php

namespace App\Controller\Admin\International;

use App\Entity\International\Trip;
use App\Entity\International\Vehicle;
use App\Form\Admin\InternationalSurvey\TripType;
use App\Utility\ConfirmAction\International\DeleteTripConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/irhs")
 */
class TripController extends AbstractController
{
    private const ROUTE_PREFIX = "admin_international_trip_";

    public const ADD_ROUTE = self::ROUTE_PREFIX."add";
    public const DELETE_ROUTE = self::ROUTE_PREFIX . "delete";
    public const EDIT_ROUTE = self::ROUTE_PREFIX."edit";

    protected EntityManagerInterface $entityManager;
    protected RequestStack $requestStack;

    public function __construct(EntityManagerInterface $entityManager, RequestStack $requestStack)
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    /**
     * @Route("/vehicle/{vehicleId}/add-trip", name=self::ADD_ROUTE)
     * @Entity("vehicle", expr="repository.find(vehicleId)")
     */
    public function add(Vehicle $vehicle): Response
    {
        $trip = (new Trip())->setVehicle($vehicle);
        $this->entityManager->persist($trip);

        return $this->handleRequest($trip, 'admin/international/trip/add.html.twig');
    }

    /**
     * @Route("/trip/{tripId}/edit", name=self::EDIT_ROUTE)
     * @Entity("trip", expr="repository.find(tripId)")
     */
    public function edit(Trip $trip): Response
    {
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
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/trip/{tripId}/delete", name=self::DELETE_ROUTE)
     * @Entity("trip", expr="repository.find(tripId)")
     * @Template("admin/international/trip/delete.html.twig")
     */
    public function delete(Trip $trip, DeleteTripConfirmAction $deleteTripConfirmAction, Request $request)
    {
        $deleteTripConfirmAction->setSubject($trip);
        return $deleteTripConfirmAction->controller(
            $request,
            function() use ($trip) { return $this->generateUrl(
                SurveyController::VIEW_ROUTE,
                ['surveyId' => $trip->getVehicle()->getSurveyResponse()->getSurvey()->getId()]
            );}
        );
    }
}