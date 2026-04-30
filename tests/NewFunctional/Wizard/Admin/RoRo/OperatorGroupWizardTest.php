<?php

namespace App\Tests\NewFunctional\Wizard\Admin\RoRo;

use App\Entity\RoRo\OperatorGroup;
use App\Tests\DataFixtures\TestSpecific\OperatorGroupRepositoryFixtures;
use App\Tests\NewFunctional\Wizard\Action\Context;
use App\Tests\NewFunctional\Wizard\Action\PathTestAction;
use App\Tests\NewFunctional\Wizard\Admin\AbstractAdminTest;
use App\Tests\NewFunctional\Wizard\Form\FormTestCase;
use Facebook\WebDriver\WebDriverBy;

class OperatorGroupWizardTest extends AbstractAdminTest
{
    public function testAddOperatorGroup(): void
    {
        $this->initialiseTest([OperatorGroupRepositoryFixtures::class]);
        $this->assertEntityCountInDatabase(OperatorGroup::class, 2);

        $this->clickLinkContaining('Operator groups');
        $this->clickLinkContaining('Add operator group');

        $this->formTestAction('/operator-groups/add/choose-name', 'operator_group_button_group_continue', [
            new FormTestCase([
                'operator_group[name]' => 'Cherry',
            ], [], null, true),
        ]);

        $this->formTestAction('/operator-groups/add/preview', 'form_button_group_continue', [
            new FormTestCase([], [], null, true),
        ]);

        $this->assertNotificationBannerContains('Operator group Cherry was successfully added');
        $this->assertEntityCountInDatabase(OperatorGroup::class, 3);
        $this->assertOperatorGroupExists('Cherry');
    }

    public function testEditOperatorGroup(): void
    {
        $this->initialiseTest([OperatorGroupRepositoryFixtures::class]);

        $this->clickLinkContaining('Operator groups');
        $this->clickLinkContaining('edit', 0);

        $this->formTestAction(
            '#^/operator-groups/[^/]+/edit/choose-name$#',
            'operator_group_button_group_continue',
            [
                new FormTestCase([
                    'operator_group[name]' => 'Apricot',
                ], [], null, true),
            ],
            [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true],
        );

        $this->formTestAction(
            '#^/operator-groups/[^/]+/edit/preview$#',
            'form_button_group_continue',
            [
                new FormTestCase([], [], null, true),
            ],
            [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true],
        );

        $this->assertNotificationBannerContains('Operator group Apricot was successfully edited');
        $this->assertEntityCountInDatabase(OperatorGroup::class, 2);
        $this->assertOperatorGroupExists('Apricot');
    }

    protected function assertNotificationBannerContains(string $expectedText): void
    {
        $this->callbackTestAction(function (Context $context) use ($expectedText) {
            $banner = $context->getClient()->findElement(
                WebDriverBy::cssSelector('.govuk-notification-banner__content')
            );
            $context->getTestCase()->assertStringContainsString(
                $expectedText,
                $banner->getText(),
                "Expected notification banner to contain '{$expectedText}'"
            );
        });
    }

    protected function assertOperatorGroupExists(string $name): void
    {
        $this->callbackTestAction(function (Context $context) use ($name) {
            $test = $context->getTestCase();
            $entityManager = $context->getEntityManager();
            $entityManager->clear();

            $groups = $entityManager->getRepository(OperatorGroup::class)->findBy(['name' => $name]);
            $test->assertCount(1, $groups, "Expected operator group named '{$name}' to exist");
        });
    }
}
