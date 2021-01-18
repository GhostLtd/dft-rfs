<?php

namespace App\Controller\Admin\Domestic;

use App\Entity\Domestic\Survey;
use App\Entity\PasscodeUser;
use App\Form\Admin\DomesticSurvey\AddSurveyType;
use App\Form\Admin\DomesticSurvey\DeleteSurveyType;
use App\Utility\PasscodeGenerator;
use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/csrgt/survey", name="admin_domestic_")
 */
class SurveyDeleteController extends AbstractController
{
    /**
     * @Route("/delete/{survey}", name="surveys_delete")
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param Survey $survey
     * @return Response
     */
    public function delete(Request $request, EntityManagerInterface $entityManager, Survey $survey): Response
    {
        /** @var Form $form */
        $form = $this->createForm(DeleteSurveyType::class);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            if ($form->getClickedButton()->getName() === 'delete') {
                if ($survey->getResponse()) {
                    foreach ($survey->getResponse()->getDays() as $day) {
                        foreach ($day->getStops() as $stop) {
                            $entityManager->remove($stop);
                        }
                        if ($day->getSummary()) $entityManager->remove($day->getSummary());
                        $entityManager->remove($day);
                    }
                    $entityManager->remove($survey->getResponse());
                }
                $entityManager->remove($survey);
                $entityManager->flush();

                $this->addFlash('notification', new NotificationBanner('Success', "Survey successfully deleted", "The survey for {$survey->getRegistrationMark()} was deleted.", ['type' => 'success']));

                return $this->redirectToRoute('admin_domestic_surveys', ['type' => $survey->getIsNorthernIreland() ? 'ni' : 'gb']);
            } else {
                $this->addFlash('notification', new NotificationBanner('Important', 'Survey not deleted', "The request to delete this survey was cancelled."));

                return $this->redirectToRoute('admin_domestic_surveydetails', [
                    'survey' => $survey->getId()
                ]);
            }
        }

        return $this->render('admin/domestic/surveys/delete.html.twig', [
            'survey' => $survey,
            'form' => $form->createView(),
        ]);
    }
}
