<?php

namespace App\Tests\Functional\Domestic;

use App\Entity\Domestic\SurveyResponse;
use App\Entity\Embeddable\Address;
use App\Tests\DataFixtures\Domestic\ResponseFixtures;
use App\Tests\Functional\AbstractWizardTestCase;
use App\Tests\Functional\Wizard\CallbackSingleEntityDatabaseTestCase;
use App\Tests\Functional\Wizard\FormTestCase;
use App\Tests\Functional\Wizard\WizardStepUrlTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class InitialDetailsEditTest extends AbstractWizardTestCase
{
    protected static string $baseUrl = "/domestic-survey/initial-details";

    public static function wizardData(): array
    {
        $address = (new Address())
            ->setLine1('30 Testing Street')
            ->setLine2('Testington')
            ->setLine3('West Testford')
            ->setPostcode('TE20 1AA');

        $unableToCompleteDate = [
            "day" => "17",
            "month" => "1",
            "year" => "2021",
        ];

        $unableToCompleteDateTime = new \DateTime("2021-01-17");

        $name = "Percy";
        $phone = "01818118181";
        $email = 'test@example.com';

        return [
            'Change initial details' => [
                '/change-contact-details',
                [ResponseFixtures::class], // extra fixtures
                [
                    new WizardStepUrlTestCase(self::$baseUrl."/change-contact-details", "contact_details_button_group_continue", [
                        new FormTestCase([
                            "contact_details" => [
                                "contactName" => null,
                                "contactTelephone" => null,
                                "contactEmail" => null,
                            ]
                        ], [
                            "#contact_details_contactName",
                            "#contact_details_contactTelephone",
                            "#contact_details_contactEmail",
                        ]),
                        new FormTestCase([
                            "contact_details" => [
                                "contactName" => null,
                                "contactTelephone" => $phone,
                                "contactEmail" => null,
                            ]
                        ], [
                            "#contact_details_contactName",
                        ]),
                        new FormTestCase([
                            "contact_details" => [
                                "contactName" => null,
                                "contactTelephone" => null,
                                "contactEmail" => $email,
                            ]
                        ], [
                            "#contact_details_contactName",
                        ]),
                        new FormTestCase([
                            "contact_details" => [
                                "contactName" => "Test",
                                "contactTelephone" => null,
                                "contactEmail" => $email,
                            ]
                        ]),
                    ]),
                    new CallbackSingleEntityDatabaseTestCase(SurveyResponse::class, function(SurveyResponse $response) use ($email) {
                        self::assertEquals("Test", $response->getContactName());
                        self::assertEquals($email, $response->getContactEmail());
                        self::assertEquals(null, $response->getContactTelephone());
                    })
                ],
            ],
            "Change possession status: yes" => [
                '/in-possession',
                [ResponseFixtures::class], // extra fixtures
                [
                    new WizardStepUrlTestCase(self::$baseUrl."/in-possession", "in_possession_of_vehicle_button_group_continue", [
                        new FormTestCase([
                            "in_possession_of_vehicle" => [
                                "isInPossessionOfVehicle" => SurveyResponse::IN_POSSESSION_YES,
                            ]
                        ]),
                    ]),
                    new CallbackSingleEntityDatabaseTestCase(SurveyResponse::class, function(SurveyResponse $response) {
                        self::assertEquals(SurveyResponse::IN_POSSESSION_YES, $response->getIsInPossessionOfVehicle());
                    })
                ],
            ],
            "Change possession status: on hire" => [
                '/in-possession',
                [ResponseFixtures::class], // extra fixtures
                [
                    new WizardStepUrlTestCase(self::$baseUrl."/in-possession", "in_possession_of_vehicle_button_group_continue", [
                        new FormTestCase([
                            "in_possession_of_vehicle" => [
                                "isInPossessionOfVehicle" => SurveyResponse::IN_POSSESSION_ON_HIRE,
                            ]
                        ]),
                    ]),
                    new WizardStepUrlTestCase(self::$baseUrl."/hiree-details", "hiree_details_button_group_continue", [
                        new FormTestCase([], [
                            '#hiree_details_hireeName',
                            '#hiree_details_hireeTelephone',
                            '#hiree_details_hireeEmail',
                            '#hiree_details_hireeAddress',
                        ]),
                        new FormTestCase([
                            'hiree_details' => [
                                'hireeTelephone' => $phone,
                            ]
                        ], [
                            '#hiree_details_hireeName',
                        ]),
                        new FormTestCase([
                            'hiree_details' => [
                                'hireeEmail' => $email,
                            ]
                        ], [
                            '#hiree_details_hireeName',
                        ]),
                        new FormTestCase([
                            'hiree_details' => [
                                'hireeAddress' => $address->toArray(),
                            ]
                        ], [
                            '#hiree_details_hireeName',
                        ]),
                        new FormTestCase([
                            'hiree_details' => [
                                'hireeName' => $name,
                                'hireeTelephone' => $phone,
                                'hireeEmail' => $email,
                                'hireeAddress' => $address->toArray(),
                            ]
                        ]),
                    ]),
                    new CallbackSingleEntityDatabaseTestCase(SurveyResponse::class, function(SurveyResponse $response) use ($address, $email, $name, $phone) {
                        self::assertEquals(SurveyResponse::IN_POSSESSION_ON_HIRE, $response->getIsInPossessionOfVehicle());
                        self::assertEquals($name, $response->getHireeName());
                        self::assertEquals($phone, $response->getHireeTelephone());
                        self::assertEquals($email, $response->getHireeEmail());
                        self::assertEquals($address, $response->getHireeAddress());
                    })
                ],
            ],
            "Change possession status: sold" => [
                '/in-possession',
                [ResponseFixtures::class], // extra fixtures
                [
                    new WizardStepUrlTestCase(self::$baseUrl."/in-possession", "in_possession_of_vehicle_button_group_continue", [
                        new FormTestCase([
                            "in_possession_of_vehicle" => [
                                "isInPossessionOfVehicle" => SurveyResponse::IN_POSSESSION_SOLD,
                            ]
                        ]),
                    ]),
                    new WizardStepUrlTestCase(self::$baseUrl."/sold-details", "sold_details_button_group_continue", [
                        new FormTestCase([], [
                            "#sold_details_soldDate",
                            "#sold_details_newOwnerName",
                            "#sold_details_newOwnerTelephone",
                            "#sold_details_newOwnerEmail",
                            "#sold_details_newOwnerAddress",
                        ]),
                        new FormTestCase([
                            "sold_details" => [
                                "soldDate" => $unableToCompleteDate,
                                "newOwnerTelephone" => $phone,
                            ]
                        ], [
                            '#sold_details_newOwnerName',
                        ]),
                        new FormTestCase([
                            "sold_details" => [
                                "soldDate" => $unableToCompleteDate,
                                "newOwnerEmail" => $email,
                            ]
                        ], [
                            '#sold_details_newOwnerName',
                        ]),
                        new FormTestCase([
                            "sold_details" => [
                                "soldDate" => $unableToCompleteDate,
                                "newOwnerAddress" => $address->toArray(),
                            ]
                        ], [
                            '#sold_details_newOwnerName',
                        ]),
                        new FormTestCase([
                            "sold_details" => [
                                "soldDate" => $unableToCompleteDate,
                                "newOwnerName" => $name,
                                "newOwnerTelephone" => $phone,
                                "newOwnerEmail" => $email,
                                "newOwnerAddress" => $address->toArray(),
                            ]
                        ]),
                    ]),
                    new CallbackSingleEntityDatabaseTestCase(SurveyResponse::class, function(SurveyResponse $response) use ($address, $email, $name, $phone, $unableToCompleteDateTime) {
                        self::assertEquals(SurveyResponse::IN_POSSESSION_SOLD, $response->getIsInPossessionOfVehicle());
                        self::assertEquals($name, $response->getNewOwnerName());
                        self::assertEquals($phone, $response->getNewOwnerTelephone());
                        self::assertEquals($email, $response->getNewOwnerEmail());
                        self::assertEquals($address, $response->getNewOwnerAddress());
                        self::assertEquals($unableToCompleteDateTime, $response->getUnableToCompleteDate());
                    })
                ],
            ],
            "Change possession status: scrapped" => [
                '/in-possession',
                [ResponseFixtures::class], // extra fixtures
                [
                    new WizardStepUrlTestCase(self::$baseUrl."/in-possession", "in_possession_of_vehicle_button_group_continue", [
                        new FormTestCase([
                            "in_possession_of_vehicle" => [
                                "isInPossessionOfVehicle" => SurveyResponse::IN_POSSESSION_SCRAPPED_OR_STOLEN,
                            ]
                        ]),
                    ]),
                    new WizardStepUrlTestCase(self::$baseUrl."/scrapped-details", "scrapped_details_button_group_continue", [
                        new FormTestCase([], [
                            "#scrapped_details_scrappedDate",
                        ]),
                        new FormTestCase([
                            "scrapped_details" => [
                                "scrappedDate" => $unableToCompleteDate,
                            ]
                        ]),
                    ]),
                    new CallbackSingleEntityDatabaseTestCase(SurveyResponse::class, function(SurveyResponse $response) use ($unableToCompleteDateTime) {
                        self::assertEquals(SurveyResponse::IN_POSSESSION_SCRAPPED_OR_STOLEN, $response->getIsInPossessionOfVehicle());
                        self::assertEquals($unableToCompleteDateTime, $response->getUnableToCompleteDate());
                    })
                ],
            ],
        ];
    }


    #[DataProvider('wizardData')]
    public function testInitialDetailsEditWizards($startRelativeUrl, $fixtures, $wizardData)
    {
        $browser = $this->getBrowserLoadFixturesAndLogin($fixtures);
        $browser->request('GET', self::$baseUrl."{$startRelativeUrl}");

        $this->doWizardTest($browser, $wizardData);
    }
}