<?php

namespace App\Controller\Admin\International\Survey;

use App\Entity\International\Vehicle;
use App\Form\Admin\InternationalSurvey\VehicleType;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VehicleController extends AbstractController
{
    public const EDIT_ROUTE = "admin_international_vehicle_edit";

    /**
     * @Route("/vehice/{vehicleId}/edit", name=self::EDIT_ROUTE)
     * @Entity("vehicle", expr="repository.find(vehicleId)")
     */
    public function edit(Vehicle $vehicle, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(VehicleType::class, $vehicle);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $entityManager->flush();
                return new RedirectResponse(
                    $this->generateUrl(SurveyController::VIEW_ROUTE, ['surveyId' => $vehicle->getSurveyResponse()->getSurvey()->getId()]).
                    "#{$vehicle->getId()}");
            }
        }

        return $this->render('admin/international/vehicle/view.html.twig', [
            'vehicle' => $vehicle,
            'form' => $form->createView(),
        ]);
    }
}