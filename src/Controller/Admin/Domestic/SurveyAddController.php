<?php

namespace App\Controller\Admin\Domestic;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\Embeddable\LongAddress;
use App\Form\Admin\DomesticSurvey\AddSurveyType;
use App\Repository\Domestic\SurveyRepository;
use App\Utility\PasscodeFormatter;
use App\Utility\PasscodeGenerator;
use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\WorkflowInterface;

class SurveyAddController extends AbstractController
{
    public function __construct(protected SurveyRepository $surveyRepository, protected WorkflowInterface $domesticSurveyStateMachine)
    {
    }

    #[Route(path: '/csrgt/survey-add/', name: SurveyController::ADD_ROUTE)]
    #[Route(path: '/csrgt/survey-reissue/{originalSurveyId}', name: 'admin_domestic_survey_reissue')]
    public function add(Request $request, EntityManagerInterface $entityManager, PasscodeGenerator $passcodeGenerator, string $originalSurveyId = null): Response
    {
        if ($originalSurveyId) {
            $originalSurvey = $this->surveyRepository->find($originalSurveyId);
            $survey = $this->createReissueSurveyFromOriginal($originalSurvey);
        } else {
            $originalSurvey = null;
            $survey = null;
        }
        $isReissue = !empty($survey);

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

                if ($isReissue) {
                    $this->domesticSurveyStateMachine->apply($originalSurvey, 'reissue');
                }

                $entityManager->persist($survey);
                $entityManager->flush();

                return $this->render('admin/domestic/surveys/add-success.html.twig', [
                    'survey' => $survey,
                    'password' => PasscodeFormatter::formatPasscode($passcodeGenerator->getPasswordForUser($survey->getPasscodeUser())),
                    'username' => PasscodeFormatter::formatPasscode($survey->getPasscodeUser()->getUserIdentifier()),
                ]);
            }
        }

        return $this->render('admin/domestic/surveys/add.html.twig', [
            'form' => $form,
        ]);
    }

    protected function createReissueSurveyFromOriginal(?Survey $originalSurvey): Survey
    {
        if (!$originalSurvey || !$this->domesticSurveyStateMachine->can($originalSurvey, 'reissue')) {
            throw new BadRequestHttpException();
        }

        $isSold = $originalSurvey->getResponse()->getIsInPossessionOfVehicle() === SurveyResponse::IN_POSSESSION_SOLD;

        $originalResponse = $originalSurvey->getResponse();

        $survey = (new Survey())
            ->setRegistrationMark($originalSurvey->getRegistrationMark())
            ->setInvitationAddress($isSold
                ? LongAddress::createFromAddress($originalResponse->getNewOwnerName(), $originalResponse->getNewOwnerAddress())
                : LongAddress::createFromAddress($originalResponse->getHireeName(), $originalResponse->getHireeAddress())
            )
            ->setInvitationEmails($isSold
                ? $originalResponse->getNewOwnerEmail()
                : $originalResponse->getHireeEmail()
            )
            ->setIsNorthernIreland($originalSurvey->getIsNorthernIreland())
            ->setSurveyPeriodStart($originalSurvey->getSurveyPeriodStart());

        $originalSurvey->setReissuedSurvey($survey);

        return $survey;
    }
}
