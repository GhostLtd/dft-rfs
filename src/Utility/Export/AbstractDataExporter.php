<?php


namespace App\Utility\Export;


use App\Entity\SurveyInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

abstract class AbstractDataExporter
{
    protected WorkflowInterface $workflow;
    protected EntityManagerInterface $entityManager;

    public function __construct(WorkflowInterface $workflow, EntityManagerInterface $entityManager)
    {
        $this->workflow = $workflow;
        $this->entityManager = $entityManager;
    }

    /**
     * @param SurveyInterface[] | Collection $surveys
     */
    protected function startExport($surveys): array
    {
        return $this->attemptTransition('start_export', $surveys);
    }

    /**
     * @param SurveyInterface[] | Collection $surveys
     */
    protected function confirmExport($surveys): array
    {
        return $this->attemptTransition('confirm_export', $surveys);
    }

    /**
     * @param SurveyInterface[] | Collection $surveys
     */
    protected function cancelExport($surveys, ?array $onlyFor = null): array
    {
        return $this->attemptTransition('cancel_export', $surveys, $onlyFor);
    }

    /**
     * @param SurveyInterface[] | Collection $surveys
     */
    private function attemptTransition(string $transitionName, $surveys, ?array $onlyFor = null): array
    {
        $transitionedIds = [];
        foreach ($surveys as $survey)
        {
            if ($onlyFor !== null && !in_array($survey->getId(), $onlyFor)) {
                continue;
            }

            if ($this->workflow->can($survey, $transitionName)) {
                $this->workflow->apply($survey, $transitionName);
                $transitionedIds[] = $survey->getId();
            }
        }
        $this->entityManager->flush();
        return $transitionedIds;
    }
}