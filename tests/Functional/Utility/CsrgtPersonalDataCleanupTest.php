<?php

namespace App\Tests\Functional\Utility;

use App\Entity\Domestic\Survey;
use App\Entity\LongAddress;
use App\Entity\PasscodeUser;
use App\Entity\SurveyStateInterface;

class CsrgtPersonalDataCleanupTest extends AbstractPersonalDataCleanupTest
{
    public function dataCleanup(): array
    {
        return [
            // Q1 2021 -> Q1 2020
            //   Testing boundary of Q1 2020
            ['2021-01-01 00:00:00', '2019-12-29 00:00:00', false, true],  // Q4 2019 should be deleted
            ['2021-01-01 00:00:00', '2020-12-30 00:00:00', false, false], // Q1 2020 should NOT be deleted

            // Q1 2021 -> Q1 2020
            //   Testing boundary of Q1 2021
            ['2020-12-28 00:00:00', '2019-10-01 00:00:00', false, true],  // This is Q1 2021, so Q4 2019 should be deleted
            ['2020-12-27 00:00:00', '2019-10-01 00:00:00', false, false], // This is Q4 2020, so Q4 2019 should NOT be deleted

            // Now again, but with an audit log entry flagging a cleanup as having occurred
            // Since it's already happened, we shouldn't do another cleanup
            ['2021-01-01 00:00:00', '2019-12-29 00:00:00', true, false],
            ['2021-01-01 00:00:00', '2020-12-30 00:00:00', true, false],
            ['2020-12-28 00:00:00', '2019-10-01 00:00:00', true, false],
            ['2020-12-27 00:00:00', '2019-10-01 00:00:00', true, false],
        ];
    }

    protected function createSurveyFixture(\DateTime $surveyPeriodEnd, bool $createAuditLogEntry): Survey
    {
        $user = $this->fixtureReferenceRepository->getReference('user:frontend', PasscodeUser::class);
        $surveyPeriodStart = (clone $surveyPeriodEnd)->modify('-7 days');

        $address = (new LongAddress())
            ->setLine1('Whatever');

        $survey = (new Survey())
            ->setSurveyPeriodStart($surveyPeriodStart)
            ->setSurveyPeriodEnd($surveyPeriodEnd)
            ->setIsNorthernIreland(true)
            ->setRegistrationMark('AB01 ABC')
            ->setInvitationAddress($address)
            ->setPasscodeUser($user)
            ->setState(SurveyStateInterface::STATE_NEW);

        $this->entityManager->persist($survey);

        if ($createAuditLogEntry) {
            $this->createAuditLogEntry($survey);
        }

        $this->entityManager->flush();
        return $survey;
    }
}
