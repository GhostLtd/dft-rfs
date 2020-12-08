<?php

namespace App\Controller\Admin\Domestic;

use App\Entity\Domestic\Survey;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class SurveysController
 * @package App\Controller\Admin\Domestic
 *
 * @Route("/csrgt/{type}", name="admin_domestic_", requirements={"type": "gb|ni"})
 */
class SurveysController extends AbstractController
{
    /**
     * @param $type
     * @return Response
     * @Route("/surveys", name="surveys")
     */
    public function index($type): Response
    {
        return $this->render('admin/domestic/surveys/index.html.twig', [
            'surveys' => $this->getDoctrine()->getRepository(Survey::class)->findByTypeWithResponseAndVehicle($type === 'ni'),
        ]);
    }

    /**
     * @param $type
     * @param Survey $survey
     * @return Response
     * @Route("/surveys/{{ survey }}", name="surveydetails")
     */
    public function viewDetails($type, Survey $survey): Response
    {
        return $this->render('admin/domestic/surveys/view.html.twig', [
            'survey' => $survey,
        ]);
    }
}
