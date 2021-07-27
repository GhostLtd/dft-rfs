<?php

namespace App\Controller\Admin\International;

use App\Entity\International\Survey;
use App\Form\Admin\InternationalSurvey\AddSurveyType;
use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SurveyAddController extends AbstractController
{
    /**
     * @Route("/irhs/survey-add/", name=SurveyController::ADD_ROUTE)
     */
    public function add(Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AddSurveyType::class);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            $cancel = $form->get('cancel');
            if ($cancel instanceof SubmitButton && $cancel->isClicked()) {
                return $this->redirectToRoute('admin_index');
            }

            if ($form->isValid()) {
                /** @var Survey $survey */
                $survey = $form->getData();
                $surveyPeriodEnd = clone $survey->getSurveyPeriodStart();
                $periodDays = $form->get('surveyPeriodInDays')->getData() - 1;
                $surveyPeriodEnd->add(new DateInterval("P{$periodDays}D"));
                $survey->setSurveyPeriodEnd($surveyPeriodEnd);

                $entityManager->persist($survey->getCompany());
                $entityManager->persist($survey);
                $entityManager->flush();

                return $this->render("admin/international/surveys/add-success.html.twig", [
                    'survey' => $survey,
                ]);
            }
        }

        return $this->render("admin/international/surveys/add.html.twig", [
            'form' => $form->createView(),
        ]);
    }
}