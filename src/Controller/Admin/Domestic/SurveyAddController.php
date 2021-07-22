<?php

namespace App\Controller\Admin\Domestic;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\LongAddress;
use App\Entity\SurveyInterface;
use App\Form\Admin\DomesticSurvey\AddSurveyType;
use App\Repository\Domestic\SurveyRepository;
use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class SurveyAddController extends AbstractController
{
    /**
     * @Route("/csrgt/survey-add/", name=SurveyController::ADD_ROUTE)
     * @Route("/csrgt/survey-reissue/{originalSurveyId}", name="admin_domestic_survey_reissue")
     */
    public function add(Request $request, EntityManagerInterface $entityManager, SurveyRepository $surveyRepository, string $originalSurveyId = null): Response
    {
        if ($originalSurveyId) {
            /** @var ?Survey $originalSurvey */
            $originalSurvey = $surveyRepository->find($originalSurveyId);

            if (!$originalSurvey ||
                (
                    $originalSurvey->getState() !== SurveyInterface::STATE_CLOSED ||
                    $originalSurvey->getResponse() === null ||
                    $originalSurvey->getReissuedSurvey() !== null ||
                    !in_array($originalSurvey->getResponse()->getIsInPossessionOfVehicle(), [SurveyResponse::IN_POSSESSION_SOLD, SurveyResponse::IN_POSSESSION_ON_HIRE])
                )
            ) {
                throw new NotFoundHttpException();
            }

            $originalResponse = $originalSurvey->getResponse();

            if ($originalResponse->getIsInPossessionOfVehicle() === SurveyResponse::IN_POSSESSION_SOLD) {
                $newInvitationAddress = LongAddress::createFromAddress($originalResponse->getNewOwnerName(), $originalResponse->getNewOwnerAddress());
                $newInvitationEmail = $originalResponse->getNewOwnerEmail();
            } else {
                $newInvitationAddress = LongAddress::createFromAddress($originalResponse->getHireeName(), $originalResponse->getHireeAddress());
                $newInvitationEmail = $originalResponse->getHireeEmail();
            }

            $survey = (new Survey())
                ->setRegistrationMark($originalSurvey->getRegistrationMark())
                ->setInvitationAddress($newInvitationAddress)
//              At the moment, email invitations are disabled
//              ->setInvitationEmail($newInvitationEmail)
                ->setIsNorthernIreland($originalSurvey->getIsNorthernIreland())
                ->setSurveyPeriodStart($originalSurvey->getSurveyPeriodStart());

            $originalSurvey
                ->setReissuedSurvey($survey)
                ->setState(Survey::STATE_REISSUED);

            $isReissue = true;
        } else {
            $survey = null;
            $isReissue = false;
        }

        $form = $this->createForm(AddSurveyType::class, $survey, ['is_reissue' => $isReissue]);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $survey = $form->getData();

                if (!$survey instanceof Survey) {
                    throw new BadRequestHttpException();
                }

                $surveyPeriodEnd = clone $survey->getSurveyPeriodStart();
                $surveyPeriodEnd->add(new DateInterval('P6D'));

                $survey
                    ->setSurveyPeriodEnd($surveyPeriodEnd);

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
