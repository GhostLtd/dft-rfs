<?php

namespace App\Form\Validator;

use App\Entity\Domestic\Vehicle as DomesticVehicle;
use App\Entity\International\Vehicle as InternationalVehicle;
use App\Repository\International\VehicleRepository as InternationalVehicleRepository;
use App\Utility\RegistrationMarkHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ValidRegistrationValidator extends ConstraintValidator
{
    protected $entityManager;

    public function __construct(EntityManagerInterface  $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ValidRegistration) {
            throw new UnexpectedTypeException($constraint, ValidRegistration::class);
        }

        if ($value instanceof DomesticVehicle || $value instanceof InternationalVehicle) {
            $registrationMark = $value->getRegistrationMark();

            if (!$registrationMark) {
                return;
            }

            $helper = new RegistrationMarkHelper($registrationMark);

            if (!$helper->isValid()) {
                $this->context
                    ->buildViolation($constraint->validMessage)
                    ->atPath('registrationMark')
                    ->addViolation();
            } else if ($value instanceof InternationalVehicle) {
                /** @var InternationalVehicleRepository $repository */
                $repository = $this->entityManager->getRepository(InternationalVehicle::class);

                // We don't check whether the vehicle already exists for Domestic as there's a one-to-one mapping
                // between DomesticSurveyResponse and DomesticVehicle (compared to many-to-one for International)
                if ($repository->registrationMarkAlreadyExists($value)) {
                    $this->context
                        ->buildViolation($constraint->alreadyExistsMessage)
                        ->atPath('registrationMark')
                        ->addViolation();
                }
            }
        } else {
            throw new UnexpectedValueException($value, 'DomesticVehicle|InternationalVehicle');
        }
    }
}