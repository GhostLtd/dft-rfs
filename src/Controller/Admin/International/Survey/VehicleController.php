<?php

namespace App\Controller\Admin\International\Survey;

use App\Entity\International\Action;
use App\Entity\International\Survey;
use App\Entity\International\Vehicle;
use App\Form\Admin\InternationalSurvey\TripDeleteType;
use App\Form\Admin\InternationalSurvey\VehicleType;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/irhs")
 */
class VehicleController extends AbstractController
{
    private const ROUTE_PREFIX = "admin_international_vehicle_";

    public const ADD_ROUTE = self::ROUTE_PREFIX."add";
    public const DELETE_ROUTE = self::ROUTE_PREFIX . "delete";
    public const EDIT_ROUTE = self::ROUTE_PREFIX."edit";

    protected $entityManager;
    protected $requestStack;

    public function __construct(EntityManagerInterface $entityManager, RequestStack $requestStack)
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    /**
     * @Route("/survey/{surveyId}/add-vehicle", name=self::ADD_ROUTE)
     * @Entity("survey", expr="repository.find(surveyId)")
     */
    public function add(Survey $survey): Response
    {
        $response = $survey->getResponse();

        if (!$response) {
            throw new NotFoundHttpException();
        }

        $vehicle = (new Vehicle())->setSurveyResponse($response);
        $this->entityManager->persist($vehicle);

        return $this->handleRequest($vehicle, 'admin/international/vehicle/add.html.twig', ['placeholders' => true]);
    }

    /**
     * @Route("/vehicle/{vehicleId}/edit", name=self::EDIT_ROUTE)
     * @Entity("vehicle", expr="repository.find(vehicleId)")
     */
    public function edit(Vehicle $vehicle): Response
    {
        return $this->handleRequest($vehicle, 'admin/international/vehicle/edit.html.twig');
    }

    protected function handleRequest(Vehicle $vehicle, string $template, array $formOptions = []): Response
    {
        $form = $this->createForm(VehicleType::class, $vehicle, $formOptions);
        $request = $this->requestStack->getCurrentRequest();

        $unmodifiedVehicle = clone $vehicle;

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            $isValid = $form->isValid();
            if ($isValid) {
                $this->entityManager->flush();
            }

            $cancel = $form->get('cancel');
            if ($isValid || ($cancel instanceof SubmitButton && $cancel->isClicked())) {
                return new RedirectResponse(
                    $this->generateUrl(SurveyController::VIEW_ROUTE, ['surveyId' => $vehicle->getSurveyResponse()->getSurvey()->getId()]).
                    "#{$vehicle->getId()}");
            }
        }

        return $this->render($template, [
            'vehicle' => $unmodifiedVehicle,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/vehicle/{vehicleId}/delete", name=self::DELETE_ROUTE)
     * @Entity("vehicle", expr="repository.find(vehicleId)")
     */
    public function delete(Vehicle $vehicle, Request $request): Response
    {
        $form = $this->createForm(TripDeleteType::class);

        $survey = $vehicle->getSurveyResponse()->getSurvey();
        $redirectUrl = $this->generateUrl(SurveyController::VIEW_ROUTE, ['surveyId' => $survey->getId()]);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            $delete = $form->get('delete');
            if ($delete instanceof SubmitButton && $delete->isClicked()) {
                foreach($vehicle->getTrips() as $trip) {
                    foreach ($trip->getActions()->filter(fn(Action $action) => $action->getLoading()) as $action) {
                        foreach ($action->getUnloadingActions() as $unloadingAction) {
                            $this->entityManager->remove($unloadingAction);
                        }
                        $this->entityManager->remove($action);
                    }
                    $this->entityManager->remove($trip);
                }
                $this->entityManager->remove($vehicle);
                $this->entityManager->flush();

                $this->addFlash('notification', new NotificationBanner('Success', "Vehicle successfully deleted", "The vehicle was deleted.", ['type' => 'success']));
                return new RedirectResponse($redirectUrl);
            } else {
                $this->addFlash('notification', new NotificationBanner('Important', 'Vehicle not deleted', "The request to delete this vehicle was cancelled."));
                return new RedirectResponse($redirectUrl);
            }
        }

        return $this->render('admin/international/vehicle/delete.html.twig', [
            'vehicle' => $vehicle,
            'form' => $form->createView(),
        ]);
    }
}