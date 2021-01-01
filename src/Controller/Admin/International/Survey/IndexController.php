<?php


namespace App\Controller\Admin\International\Survey;


use App\Repository\International\SurveyRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/irhs/surveys")
 * Class IndexController
 * @package App\Controller\Admin\International\Survey
 */
class IndexController extends AbstractController
{
    /**
     * @Route("")
     * @Template("admin/international/surveys/index.html.twig")
     * @param SurveyRepository $surveyRepository
     * @return array
     */
    public function index(SurveyRepository $surveyRepository)
    {
        return [
            'surveys' => $surveyRepository->findAll(),
        ];
    }

    /**
     * @Route("")
     * @Template("admin/international/surveys/view.html.twig")
     */
    public function view()
    {
        return [];
    }
}