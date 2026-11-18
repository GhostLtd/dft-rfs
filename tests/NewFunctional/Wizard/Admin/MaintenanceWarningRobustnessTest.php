<?php

namespace App\Tests\NewFunctional\Wizard\Admin;

use App\Entity\Utility\MaintenanceWarning;
use App\Tests\DataFixtures\MaintenanceWarningFixtures;

/**
 * The maintenance-warning form, submitted without its button (as form.submit() does, or anything else that submits a
 * form without clicking one), used to give a 500 (found by DAST: local/2026-11-17-dast-bug-log.md, B11). It's
 * treated like Save instead.
 */
class MaintenanceWarningRobustnessTest extends AbstractAdminTestCase
{
    public function testAddFormSubmittedWithoutAButtonShowsTheFormAgain(): void
    {
        $this->initialiseTest([]);

        $this->submitFormWithoutAButton('/maintenance-warning/add');

        // 422: Symfony's render() of a submitted, invalid form (the add form starts empty)
        $this->assertSame(422, $this->client->getResponse()->getStatusCode());
        $this->assertPathMatches('/maintenance-warning/add', false);
        $this->assertCount(0, $this->entityManager->getRepository(MaintenanceWarning::class)->findAll());
    }

    public function testEditFormSubmittedWithoutAButtonSaves(): void
    {
        $this->initialiseTest([MaintenanceWarningFixtures::class]);
        $warning = $this->fixtureReferenceRepository->getReference('maintenance-warning', MaintenanceWarning::class);

        $this->submitFormWithoutAButton("/maintenance-warning/{$warning->getId()}/edit");

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertPathMatches('/maintenance-warning', false);
    }

    private function submitFormWithoutAButton(string $path): void
    {
        $crawler = $this->client->request('GET', $path);
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());

        // A Form built from the <form> element itself has no submit button, so none is sent
        $form = $crawler->filter('form[method="post"]')->form();
        foreach (array_keys($form->getValues()) as $name) {
            $this->assertStringNotContainsString('[submit]', $name);
            $this->assertStringNotContainsString('[cancel]', $name);
        }

        $this->client->submit($form);
    }
}
