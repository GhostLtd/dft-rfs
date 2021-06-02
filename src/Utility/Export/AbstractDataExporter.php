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
    protected function startExport($surveys)
    {
        $this->attemptTransition('start_export', $surveys);
    }

    /**
     * @param SurveyInterface[] | Collection $surveys
     */
    protected function confirmExport($surveys)
    {
        $this->attemptTransition('confirm_export', $surveys);
    }

    /**
     * @param SurveyInterface[] | Collection $surveys
     */
    protected function cancelExport($surveys)
    {
        $this->attemptTransition('cancel_export', $surveys);
    }

    /**
     * @param string $transitionName
     * @param SurveyInterface[] | Collection $surveys
     */
    private function attemptTransition(string $transitionName, $surveys)
    {
        foreach ($surveys as $survey)
        {
            if ($this->workflow->can($survey, $transitionName)) {
                $this->workflow->apply($survey, $transitionName);
            }
        }
        $this->entityManager->flush();
    }
}