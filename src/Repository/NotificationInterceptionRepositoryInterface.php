<?php

namespace App\Repository;

use App\Entity\NotificationInterceptionInterface;
use App\Entity\SurveyInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;

interface NotificationInterceptionRepositoryInterface extends ServiceEntityRepositoryInterface
{
    public function findOneBySurvey(SurveyInterface $survey): ?NotificationInterceptionInterface;

    // Used for enforcing uniqueness
    public function findByAllNames($excludeId, array $names = []): array;
}