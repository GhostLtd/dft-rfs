<?php

namespace App\Tests\Functional\Utility;

use App\Entity\PasscodeUser;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\SurveyStateInterface;

class PreEnquiryDataDeleteTest extends AbstractSurveyDeleteTest
{
    public function dataDelete(): array
    {
        return [
            // Q1 2021 -> Q1 2020
            //   Testing boundary of Q1 2020
            ['2021-01-01 00:00:00', '2018-12-31 00:00:00', true],  // Q4 2018 should be deleted
            ['2021-01-01 00:00:00', '2019-01-01 00:00:00', false], // Q1 2019 should NOT be deleted

            // Q1 2021 -> Q1 2020
            //   Testing boundary of Q1 2021
            ['2021-01-01 00:00:00', '2018-10-01 00:00:00', true],  // This is Q1 2021, so Q4 2018 should be deleted
            ['2020-12-31 00:00:00', '2018-10-01 00:00:00', false], // This is Q4 2020, so Q4 2018 should NOT be deleted
        ];
    }

    protected function createSurveyFixture(\DateTime $date): PreEnquiry
    {
        $user = $this->fixtureReferenceRepository->getReference('user:frontend', PasscodeUser::class);

        $survey = (new PreEnquiry())
            ->setReferenceNumber('123')
            ->setCompanyName('Wibble')
            ->setDispatchDate($date)
            ->setPasscodeUser($user)
            ->setState(SurveyStateInterface::STATE_NEW);

        $this->entityManager->persist($survey);
        $this->entityManager->flush();

        return $survey;
    }

    protected function surveyExists(string $surveyId): bool
    {
        return $this->entityManager
            ->getRepository(PreEnquiry::class)
            ->find($surveyId) !== null;
    }
}
