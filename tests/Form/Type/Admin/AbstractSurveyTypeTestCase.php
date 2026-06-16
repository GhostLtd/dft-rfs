<?php

namespace App\Tests\Form\Type\Admin;

use App\Form\Validator\ValidRegistrationValidator;
use App\Tests\Form\Type\AbstractTypeTestCase;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractSurveyTypeTestCase extends AbstractTypeTestCase
{
    protected function getValidators(): array
    {
        $validRegistrationValidator = new ValidRegistrationValidator($this->createMock(EntityManagerInterface::class));

        return array_merge(
            parent::getValidators(),
            [
                ValidRegistrationValidator::class => $validRegistrationValidator,
            ]
        );
    }
}
