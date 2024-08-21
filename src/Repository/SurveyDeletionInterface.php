<?php

namespace App\Repository;

interface SurveyDeletionInterface
{
    public function getSurveysForDeletion(\DateTime $before): array;
}