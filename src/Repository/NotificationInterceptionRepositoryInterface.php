<?php

namespace App\Repository;

use App\Entity\SurveyInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;

interface NotificationInterceptionRepositoryInterface extends ServiceEntityRepositoryInterface
{
    public function findOneBySurvey(SurveyInterface $survey);

    // Used for enforcing uniqueness
    public function findByAllNames($excludeId, array $names = []): array;
}