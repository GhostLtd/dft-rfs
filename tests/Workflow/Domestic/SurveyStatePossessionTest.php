<?php

namespace App\Tests\Workflow\Domestic;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Workflow\WorkflowInterface;

class SurveyStatePossessionTest extends WebTestCase
{
    private WorkflowInterface $workflow;

    #[\Override]
    protected function setUp(): void
    {
        static::bootKernel();

        $container = static::getContainer()->get('test.service_container');
        $this->workflow = $container->get('state_machine.domestic_survey');
    }

    public function surveyProvider(): array
    {
        return [
            [ // first hire
                $this->createSurveyEntity([
                    'isInPossessionOfVehicle' => SurveyResponse::IN_POSSESSION_ON_HIRE,
                ]),
                ['reissue', 're_open'],
                ['approve'],
            ],
            [ // first sold
                $this->createSurveyEntity([
                    'isInPossessionOfVehicle' => SurveyResponse::IN_POSSESSION_SOLD,
                ]),
                ['reissue', 'approve', 're_open'],
                [],
            ],
            [ // scrapped
                $this->createSurveyEntity([
                    'isInPossessionOfVehicle' => SurveyResponse::IN_POSSESSION_SCRAPPED_OR_STOLEN,
                ]),
                ['approve', 're_open'],
                ['reissue'],
            ],
            [ // in possession
                $this->createSurveyEntity([
                    'isInPossessionOfVehicle' => SurveyResponse::IN_POSSESSION_YES,
                ]),
                ['approve', 're_open'],
                ['reissue'],
            ],

            [ // multi hire
                $this->createSurveyEntity([
                    'isInPossessionOfVehicle' => SurveyResponse::IN_POSSESSION_ON_HIRE,
                    'originalSurvey' => $this->createSurveyEntity([
                        'isInPossessionOfVehicle' => SurveyResponse::IN_POSSESSION_ON_HIRE,
                    ]),
                ]),
                ['approve', 're_open'],
                ['reissue'],
            ],

            [ // multi sold
                $this->createSurveyEntity([
                    'isInPossessionOfVehicle' => SurveyResponse::IN_POSSESSION_SOLD,
                    'originalSurvey' => $this->createSurveyEntity([
                        'isInPossessionOfVehicle' => SurveyResponse::IN_POSSESSION_SOLD,
                    ]),
                ]),
                ['approve', 're_open'],
                ['reissue'],
            ],

            [ // on-hire, reissued
                $this->createSurveyEntity([
                    'state' => Survey::STATE_REISSUED,
                    'isInPossessionOfVehicle' => SurveyResponse::IN_POSSESSION_ON_HIRE,
                ]),
                [],
                ['reissue', 're_open', 'reject', 'start_export', 'approve', 'un_approve'],
            ],

            [ // no response from haulier - invited
                (new Survey())->setState(Survey::STATE_INVITATION_SENT),
                ['complete'],
                ['re_open', 'reissue', 'reject', 'start_export', 'un_approve', 'approve'],
            ],

            [ // no response from haulier - closed
                (new Survey())->setState(Survey::STATE_CLOSED),
                ['approve', 're_open', 'reject'],
                ['reissue', 'start_export', 'un_approve'],
            ],

            [ // invitation failed
                (new Survey())->setState(Survey::STATE_INVITATION_FAILED),
                ['reject', 'complete'],
                ['re_open', 'reissue', 'start_export', 'un_approve', 'approve'],
            ],

        ];
    }

    /**
     * @dataProvider surveyProvider
     */
    public function testSurveyStatePossession($survey, $allowedTransitions, $notAllowedTransitions)
    {
        foreach ($allowedTransitions as $allowedTransition) {
            self::assertTrue($this->workflow->can($survey, $allowedTransition), "Transition: $allowedTransition");
        }

        foreach ($notAllowedTransitions as $notAllowedTransition) {
            self::assertFalse($this->workflow->can($survey, $notAllowedTransition), "Transition: $notAllowedTransition");
        }
    }

    protected function assertArraysSameValues($expected, $actual, $message = 'Missing (-) and excess (+) transitions')
    {
        $missing = [];
        foreach ($expected as $v) {
            if (array_search($v, $actual) === false) {
                $missing[] = $v;
            }
        }

        $excess = [];
        foreach ($actual as $v) {
            if (array_search($v, $expected) === false) {
                $excess[] = $v;
            }
        }

        $this->assertEquals($missing, $excess, $message);
    }

    protected function createSurveyEntity($config = []): ?Survey
    {
        return (new Survey())
            ->setState($config['state'] ?? Survey::STATE_CLOSED)
            ->setResponse((new SurveyResponse())
                ->setIsInPossessionOfVehicle($config['isInPossessionOfVehicle'] ?? SurveyResponse::IN_POSSESSION_YES)
            )
            ->setOriginalSurvey($config['originalSurvey'] ?? null)
            ->setReissuedSurvey($config['reissuedSurvey'] ?? null)
        ;
    }
}