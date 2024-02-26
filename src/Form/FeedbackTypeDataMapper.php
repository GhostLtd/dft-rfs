<?php

namespace App\Form;

use Symfony\Component\Form\Extension\Core\DataMapper\DataMapper;

class FeedbackTypeDataMapper extends DataMapper
{
    #[\Override]
    public function mapFormsToData(\Traversable $forms, &$data): void
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
