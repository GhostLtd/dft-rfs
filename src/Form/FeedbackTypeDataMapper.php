<?php

namespace App\Form;

use App\Entity\Feedback;
use Symfony\Component\Form\Extension\Core\DataMapper\PropertyPathMapper;
use Symfony\Component\Form\FormInterface;
use Traversable;

class FeedbackTypeDataMapper extends PropertyPathMapper
{
    /**
     * @param FormInterface[]|Traversable $forms
     * @param Feedback $data
     */
    public function mapFormsToData($forms, &$data)
    {
        parent::mapFormsToData($forms, $data);

        if ($data->getHasCompletedPaperSurvey() !== true) {
            $data->setComparisonRating(null);
            $data->setTimeToComplete(null);
        }
        if ($data->getHadIssues() !== FeedbackType::ISSUES_UNSOLVED) {
            $data->setIssueDetails(null);
        }
    }
}