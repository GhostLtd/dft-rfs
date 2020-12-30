<?php

namespace App\Controller\InternationalSurvey;

use App\Entity\International\Consignment;
use App\Entity\International\Trip;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Security("is_feature_enabled('IRHS_CONSIGNMENTS_AND_STOPS')")
 */
class ConsignmentController extends AbstractController
{
    public const SUMMARY_ROUTE = 'app_internationalsurvey_consignment_summary';

    /**
     * @Route("/international-survey/trips/{tripId}/consignment/{consignmentId}", name=self::SUMMARY_ROUTE)
     * @Entity("consignment", expr="repository.find(consignmentId)")
     * @Entity("trip", expr="repository.find(tripId)")
     */
    public function summary(Consignment $consignment, Trip $trip): Response
    {
        if ($consignment === null || $trip === null) {
            throw new NotFoundHttpException();
        }

        return $this->render('international_survey/consignment/summary.html.twig', [
            'consignment' => $consignment,
            'trip' => $trip,
        ]);
    }
}