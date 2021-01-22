<?php

namespace App\Controller\Admin\International;

use App\Entity\International\Trip;
use App\Entity\International\Vehicle;
use App\Form\Admin\InternationalSurvey\TripDeleteType;
use App\Form\Admin\InternationalSurvey\TripType;
use App\Utility\International\DeleteHelper;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
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
     */
    public function delete(Trip $trip, Request $request, DeleteHelper $deleteHelper): Response
    {
        $form = $this->createForm(TripDeleteType::class);

        $survey = $trip->getVehicle()->getSurveyResponse()->getSurvey();

        $redirectUrl = $this->generateUrl(
                SurveyController::VIEW_ROUTE,
                ['surveyId' => $survey->getId()]
            ).'#' . $trip->getId();

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            $delete = $form->get('delete');
            if ($delete instanceof SubmitButton && $delete->isClicked()) {
                $deleteHelper->deleteTrip($trip);

                $this->addFlash(NotificationBanner::FLASH_BAG_TYPE, new NotificationBanner('Success', "Trip successfully deleted", "The trip was deleted.", ['style' => NotificationBanner::STYLE_SUCCESS]));
                return new RedirectResponse($redirectUrl);
            } else {
                $this->addFlash(NotificationBanner::FLASH_BAG_TYPE, new NotificationBanner('Important', 'Trip not deleted', "The request to delete this trip was cancelled."));
                return new RedirectResponse($redirectUrl);
            }
        }

        return $this->render('admin/international/trip/delete.html.twig', [
            'trip' => $trip,
            'form' => $form->createView(),
        ]);
    }
}