<?php

namespace App\Controller\Admin\Domestic;

use App\Entity\Domestic\Survey;
use App\Form\Admin\DomesticSurvey\AddSurveyType;
use App\Repository\PasscodeUserRepository;
use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/csrgt/survey/add", name="admin_domestic_")
 */
class SurveyAddController extends AbstractController
{
    /**
     * @Route("", name="survey_add")
     */
    public function add(Request $request, PasscodeUserRepository $passcodeUserRepository, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AddSurveyType::class);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $survey = $form->getData();

                if (!$survey instanceof Survey) {
                    throw new BadRequestHttpException();
                }

                $surveyPeriodEnd = clone $survey->getSurveyPeriodStart();
                $surveyPeriodEnd->add(new DateInterval('P7D'));

                $survey
                    ->setSurveyPeriodEnd($surveyPeriodEnd)
                    ->setReminderState(Survey::REMINDER_STATE_NOT_WANTED);

                $entityManager->persist($survey);
                $entityManager->flush();

                return $this->render('admin/domestic/surveys/add-success.html.twig', [
                    'survey' => $survey,
                ]);
            }
        }

        return $this->render('admin/domestic/surveys/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
