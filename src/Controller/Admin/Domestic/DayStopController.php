<?php

namespace App\Controller\Admin\Domestic;

use App\Entity\Domestic\DayStop;
use App\Form\Admin\DomesticSurvey\Edit\DayStopType;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/csrgt/day-stops")
 */
class DayStopController extends AbstractController
{
    private const ROUTE_PREFIX = 'admin_domestic_daystop_';
    public const DELETE_ROUTE = self::ROUTE_PREFIX . 'delete';
    public const EDIT_ROUTE = self::ROUTE_PREFIX . 'edit';

    /**
     * @Route("/{stopId}", name=self::EDIT_ROUTE)
     * @Entity("stop", expr="repository.find(stopId)")
     */
    public function edit(DayStop $stop, Request $request, EntityManagerInterface $entityManager)
    {
        $survey = $stop->getDay()->getResponse()->getSurvey();

        $form = $this->createForm(DayStopType::class, $stop);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);
            $redirectResponse = new RedirectResponse($this->generateUrl(SurveyController::VIEW_ROUTE, ['surveyId' => $survey->getId()]) . '#' . $stop->getId());

            $cancel = $form->get('cancel');
            if ($cancel instanceof SubmitButton && $cancel->isClicked()) {
                return $redirectResponse;
            }

            if ($form->isValid()) {
                $entityManager->flush();
                return $redirectResponse;
            }
        }

        return $this->render("admin/domestic/stop/edit.html.twig", [
            'survey' => $survey,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{stopId}", name=self::DELETE_ROUTE)
     * @Entity("stop", expr="repository.find(stopId)")
     */
    public function delete(DayStop $stop)
    {

    }
}