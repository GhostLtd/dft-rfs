<?php

namespace App\Tests\Form\Type;

use App\ExpressionLanguage\GeneralExpressionProvider;
use App\Utility\International\LoadingWithoutUnloadingHelper;
use Ghost\GovUkFrontendBundle\Form\Extension\ChoiceTypeExtension;
use Ghost\GovUkFrontendBundle\Form\Extension\ConditionalTypeExtension;
use Ghost\GovUkFrontendBundle\Form\Extension\FormTypeExtension;
use Ghost\GovUkFrontendBundle\Form\Extension\LabelIsPageHeadingExtension;
use Ghost\GovUkFrontendBundle\Form\Extension\NotRequiredExtension;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\ExpressionValidator;
use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validation;

abstract class AbstractTypeTestCase extends TypeTestCase
{
    protected array $validators = [];

    #[\Override]
    protected function getExtensions(): array
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->setConstraintValidatorFactory($this->getConstraintValidatorFactory())
            ->getValidator();

        return [
            new ValidatorExtension($validator),
        ];
    }

    /**
     * We don't have a container (because this isn't an integration test), so we need to create our own validator factory.
     */
    protected function getConstraintValidatorFactory(): ConstraintValidatorFactoryInterface
    {
        $validators = $this->getValidators();

        foreach($validators as $class => $validator) {
            $expectedClass = $validator::class;
            if (!in_array($class, ['validator.expression', 'doctrine.orm.validator.unique']) && $expectedClass !== $class) {
                $this->fail("getValidators() key/class mismatch [key: {$class} / expected: {$expectedClass}]");
            }
        }

        return (new class($validators) extends ConstraintValidatorFactory {
            public function __construct(array $validators)
            {
                parent::__construct();
                $this->validators = $validators;
            }
        });
    }

    protected function getValidators(): array
    {
        $expressionLanguage = new ExpressionLanguage(null, [
            new GeneralExpressionProvider(new LoadingWithoutUnloadingHelper()),
        ]);

        return [
            // For some reason the expression validator is keyed differently (see ConstraintValidatorFactory)
            'validator.expression' => new ExpressionValidator($expressionLanguage),
            // mock the unique validator, because there is no container, and therefore no doctrine to query for duplicates
            // this will **always** validate, even if there are duplicates
            'doctrine.orm.validator.unique' => (new class() implements ConstraintValidatorInterface {
                public function initialize(ExecutionContextInterface $context) {}
                public function validate(mixed $value, Constraint $constraint) {}
            })
        ];
    }

    #[\Override]
    protected function getTypeExtensions(): array
    {
        return [
            new ChoiceTypeExtension(),
            new ConditionalTypeExtension(),
            new FormTypeExtension(),
            new LabelIsPageHeadingExtension(),
            new NotRequiredExtension(),
        ];
    }
}