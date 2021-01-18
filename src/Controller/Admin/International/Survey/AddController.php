<?php

namespace App\Controller\Admin\International\Survey;

use App\Entity\International\Survey;
use App\Form\Admin\InternationalSurvey\AddSurveyType;
use App\Repository\PasscodeUserRepository;
use DateInterval;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/irhs/surveys/add", name="admin_international_survey_")
 */
class AddController extends AbstractController
{
    /**
     * @Route(name="add")
     * @Template("admin/international/surveys/add.html.twig")
     */
    public function add(Request $request, PasscodeUserRepository $passcodeUserRepository): array
    {
        $form = $this->createForm(AddSurveyType::class);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                /** @var Survey $survey */
                $survey = $form->getData();
                $surveyPeriodEnd = clone $survey->getSurveyPeriodStart();
                $periodDays = $form->get('surveyPeriodInDays')->getData() - 1;
                $surveyPeriodEnd->add(new DateInterval("P{$periodDays}D"));
                $survey->setSurveyPeriodEnd($surveyPeriodEnd);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($survey->getCompany());
                $entityManager->persist($survey);
                $entityManager->flush();

                return [
                    'survey' => $survey,
                ];
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }
}