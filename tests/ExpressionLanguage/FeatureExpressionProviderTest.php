<?php

namespace App\Tests\ExpressionLanguage;

use App\ExpressionLanguage\FeatureExpressionProvider;
use App\Features;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\SyntaxError;

/**
 * Regression tests for the `is_feature_enabled` expression-language function.
 *
 * Locks in pre-migration contract:
 * - Returns false for a known-but-disabled feature.
 * - Throws SyntaxError for an unknown feature name.
 *
 * Both should remain true post-Stage-8 migration.
 */
class FeatureExpressionProviderTest extends KernelTestCase
{
    private ExpressionLanguage $expressionLanguage;

    #[\Override]
    protected function setUp(): void
    {
        self::bootKernel();
        $provider = self::getContainer()->get(FeatureExpressionProvider::class);
        $this->expressionLanguage = new ExpressionLanguage(null, [$provider]);
    }

    public function testReturnsFalseForKnownFeatureWhenNotInEnv(): void
    {
        // Default test env: APP_FEATURES is unset, so all features are disabled.
        // Reference via constant() so the assertion survives the Stage-8 value change.
        $featureValue = Features::SMARTLOOK_USER_SESSION_RECORDING;

        $result = $this->expressionLanguage->evaluate(
            sprintf("is_feature_enabled(%s)", var_export($featureValue, true)),
        );

        self::assertFalse($result);
    }

    public function testThrowsSyntaxErrorForUnknownFeature(): void
    {
        $this->expectException(SyntaxError::class);
        $this->expressionLanguage->evaluate("is_feature_enabled('definitely-not-a-real-feature')");
    }
}
