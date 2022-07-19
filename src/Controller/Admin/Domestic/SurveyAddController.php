<?php

namespace App\Controller\Admin\Domestic;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\LongAddress;
use App\Form\Admin\DomesticSurvey\AddSurveyType;
use App\Repository\Domestic\SurveyRepository;
use App\Utility\NotificationInterceptionService;
use App\Utility\PasscodeGenerator;
use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

class SurveyAddController extends AbstractController
{
    private SurveyRepository $surveyRepository;
    private WorkflowInterface $domesticSurveyStateMachine;

    public function __construct(SurveyRepository $surveyRepository, WorkflowInterface $domesticSurveyStateMachine)
    {
        $this->surveyRepository = $surveyRepository;
        $this->domesticSurveyStateMachine = $domesticSurveyStateMachine;
    }

    /**
     * @Route("/csrgt/survey-add/", name=SurveyController::ADD_ROUTE)
     * @Route("/csrgt/survey-reissue/{originalSurveyId}", name="admin_domestic_survey_reissue")
     */
    public function add(Request $request, EntityManagerInterface $entityManager, PasscodeGenerator $passcodeGenerator, NotificationInterceptionService $notificationInterception, string $originalSurveyId = null): Response
    {
        if ($originalSurveyId) {
            $originalSurvey = $this->surveyRepository->find($originalSurveyId);
            $survey = $this->getSurvey($originalSurvey);
        } else {
            $originalSurvey = null;
            $survey = null;
        }
        $isReissue = !empty($survey);

        if ($isReissue) {
            $notificationInterception->checkAndInterceptSurvey($survey);
        }

        $form = $this->createForm(AddSurveyType::class, $survey, ['is_reissue' => $isReissue]);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $survey = $form->getData();

                if (!$survey instanceof Survey) {
                    throw new BadRequestHttpException();
                }

                // We did this in the GET, but we need to do it again in case it was cleared, or it's not a reissue
                $notificationInterception->checkAndInterceptSurvey($survey);

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
                    'password' => $passcodeGenerator->getPasswordForUser($survey->getPasscodeUser()),
                ]);
            }
        }

        return $this->render('admin/domestic/surveys/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    protected function getSurvey(Survey $originalSurvey)
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
//              At the moment, email invitations are disabled
//              ->setInvitationEmail($isSold
//                    ? $originalResponse->getNewOwnerEmail()
//                    : $originalResponse->getHireeEmail()
//                )
            ->setIsNorthernIreland($originalSurvey->getIsNorthernIreland())
            ->setSurveyPeriodStart($originalSurvey->getSurveyPeriodStart());

        $originalSurvey->setReissuedSurvey($survey);

        return $survey;
    }
}
