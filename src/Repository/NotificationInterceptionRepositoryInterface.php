<?php

namespace App\Repository;

use App\Entity\International\NotificationInterception as IrhsNotificationInterception;
use App\Entity\LCNI\NotificationInterceptionInterface;
use App\Entity\NotificationInterception;
use App\Entity\SurveyInterface;
use App\Repository\Domestic\NotificationInterceptionRepository as CsrgtNotificationInterceptionRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;

interface NotificationInterceptionRepositoryInterface extends ServiceEntityRepositoryInterface
{
    public function findOneBySurvey(SurveyInterface $survey): NotificationInterception|CsrgtNotificationInterceptionRepository|IrhsNotificationInterception|null;

    public function getClassName(): string;
}
