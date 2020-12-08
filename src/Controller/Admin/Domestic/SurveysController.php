<?php

namespace App\Controller\Admin\Domestic;

use App\Entity\Domestic\Survey;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SurveysController extends AbstractController
{
    /**
     * @Route("/domestic/surveys", name="admin_domestic_surveys")
     */
    public function index(): Response
    {
        return $this->render('admin/domestic/surveys/index.html.twig', [
            'surveys' => $this->getDoctrine()->getRepository(Survey::class)->findAllWithResponseAndVehicle(),
        ]);
    }
}
