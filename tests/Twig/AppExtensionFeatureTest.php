<?php

namespace App\Tests\Twig;

use App\Features;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;
use Twig\Error\SyntaxError;

/**
 * Regression tests for the `is_feature_enabled` Twig function.
 *
 * Locks in pre-migration contract:
 * - Returns false for a known-but-disabled feature.
 * - Throws SyntaxError for an unknown feature name.
 *
 * Both should remain true post-Stage-8 migration. The only thing
 * Stage 8 changes is the constant *value* (UPPERCASE → kebab-case);
 * tests reference the constant by name, so they survive that change.
 */
class AppExtensionFeatureTest extends KernelTestCase
{
    private Environment $twig;

    #[\Override]
    protected function setUp(): void
    {
        self::bootKernel();
        $this->twig = self::getContainer()->get('twig');
    }

    public function testReturnsFalseForKnownFeatureWhenNotInEnv(): void
    {
        // Default test env: APP_FEATURES is unset, so all features are disabled.
        // The constant() call resolves at template-compile time and survives the
        // Stage-8 migration (which changes the constant *value*, not its name).
        $template = $this->twig->createTemplate(
            "{{ is_feature_enabled(constant('App\\\\Features::SMARTLOOK_USER_SESSION_RECORDING')) ? 'YES' : 'NO' }}"
        );

        self::assertSame('NO', $template->render());
    }

    public function testThrowsSyntaxErrorForUnknownFeature(): void
    {
        $template = $this->twig->createTemplate(
            "{{ is_feature_enabled('definitely-not-a-real-feature') }}"
        );

        $this->expectException(SyntaxError::class);
        $template->render();
    }
}
