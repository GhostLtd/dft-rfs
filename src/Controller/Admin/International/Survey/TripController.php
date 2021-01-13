<?php

namespace App\Controller\Admin\International\Survey;

use App\Entity\International\Trip;
use App\Form\Admin\InternationalSurvey\TripType;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TripController extends AbstractController
{
    public const EDIT_ROUTE = "admin_international_trip_edit";

    /**
     * @Route("/trip/{tripId}/edit", name=self::EDIT_ROUTE)
     * @Entity("trip", expr="repository.find(tripId)")
     */
    public function edit(Trip $trip, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TripType::class, $trip);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $submit = $form->get('submit');
                if ($submit instanceof SubmitButton && $submit->isClicked()) {
                    $entityManager->flush();
                }
                return new RedirectResponse(
                    $this->generateUrl(SurveyController::VIEW_ROUTE, ['surveyId' => $trip->getVehicle()->getSurveyResponse()->getSurvey()->getId()]).
                    "#{$trip->getId()}");
            }
        }

        return $this->render('admin/international/trip/edit.html.twig', [
            'trip' => $trip,
            'form' => $form->createView(),
        ]);
    }
}