<?php

namespace App\Tests\Utility\Domestic;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\PasscodeUser;
use App\Utility\Domestic\DeleteHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DeleteHelperTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private DeleteHelper $deleteHelper;

    protected function setUp(): void
    {
        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->deleteHelper = $container->get(DeleteHelper::class);
    }

    public function testDeletingAnInProgressSurveyWhoseResponseHasNoVehicleDoesNotCrash(): void
    {
        $passcodeUser = (new PasscodeUser())->setUsername('seed-delete-test')->setPlainPassword('x');
        $survey = (new Survey())
            ->setState('in-progress')
            ->setRegistrationMark('AB12CDE')
            ->setIsNorthernIreland(false)
            ->setSurveyPeriodStart(new \DateTime('2026-08-01'))
            ->setSurveyPeriodEnd(new \DateTime('2026-08-07'))
            ->setPasscodeUser($passcodeUser)
            ->setResponse((new SurveyResponse())->setContactEmail('driver@example.invalid'));
        $this->entityManager->persist($survey);
        $this->entityManager->flush();

        $surveyId = $survey->getId();
        $responseId = $survey->getResponse()->getId();
        $passcodeUserId = $passcodeUser->getId();

        $this->deleteHelper->deleteSurvey($survey);

        self::assertNull($this->entityManager->getRepository(Survey::class)->find($surveyId));
        self::assertNull($this->entityManager->getRepository(SurveyResponse::class)->find($responseId));
        self::assertNull($this->entityManager->getRepository(PasscodeUser::class)->find($passcodeUserId));
    }
}
