<?php

namespace App\Controller\Admin\Domestic;

use App\Entity\Domestic\Survey;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SurveysController extends AbstractController
{
    /**
     * @param $type
     * @return Response
     * @Route("/domestic/surveys/{type}", name="admin_domestic_surveys", requirements={"type": "gb|ni"})
     */
    public function index($type): Response
    {
        return $this->render('admin/domestic/surveys/index.html.twig', [
            'surveys' => $this->getDoctrine()->getRepository(Survey::class)->findByTypeWithResponseAndVehicle($type === 'ni'),
        ]);
    }
}
