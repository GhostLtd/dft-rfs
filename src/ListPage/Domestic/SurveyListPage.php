<?php

namespace App\ListPage\Domestic;

use App\Entity\Domestic\Survey;
use App\ListPage\AbstractListPage;
use App\ListPage\Domestic\Field\WeekYearFilter;
use App\ListPage\Field\ChoiceFilter;
use App\ListPage\Field\Simple;
use App\ListPage\Field\TextFilter;
use App\Repository\Domestic\SurveyRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

class SurveyListPage extends AbstractListPage
{
    protected bool $isNorthernIreland;

    /**
     * @var SurveyRepository
     */
    private SurveyRepository $repository;

    public function __construct(SurveyRepository $repository, FormFactoryInterface $formFactory, RouterInterface $router)
    {
        parent::__construct($formFactory, $router);
        $this->repository = $repository;
    }

    /**
     * @param bool $isNorthernIreland
     */
    public function setIsNorthernIreland(bool $isNorthernIreland): self
    {
        $this->isNorthernIreland = $isNorthernIreland;
        return $this;
    }

    protected function getFieldsDefinition(): array
    {
        $stateChoices = array_combine(array_map(fn($x) => ucfirst($x), Survey::STATE_CHOICES), Survey::STATE_CHOICES);
        return [
            (new WeekYearFilter('Week', 'survey.surveyPeriodStart')),
            (new Simple('Start date', 'survey.surveyPeriodStart'))->sortable(),
            (new Simple('End date', 'survey.surveyPeriodEnd'))->sortable(),
            (new TextFilter('Reg mark', 'survey.registrationMark'))->sortable(),
            (new ChoiceFilter('Status', 'survey.state', $stateChoices))->sortable(),
            (new ChoiceFilter("QA'd?", 'survey.qualityAssured', [
                'Yes' => true,
                'No' => false,
            ]))->sortable(),
            (new Simple('In possession', 'response.isInPossessionOfVehicle'))->sortable(),
            (new Simple('Reminders')),
            (new Simple("Summ.\nDays")),
            (new Simple("Stop\nDays")),
        ];
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->repository->createQueryBuilder('survey');
        return $queryBuilder
            ->select('survey, response, user, day, stop, summary')
            ->leftJoin('survey.response', 'response')
            ->leftJoin('survey.passcodeUser', 'user')
            ->leftJoin('response.days', 'day')
            ->leftJoin('day.stops', 'stop')
            ->leftJoin('day.summary', 'summary')
            ->andWhere('survey.isNorthernIreland = :isNorthernIreland')
            ->setParameter('isNorthernIreland', $this->isNorthernIreland);
    }

    protected function getDefaultOrder(): array
    {
        return [
            Simple::generateId('Start date') => 'DESC',
            Simple::generateId('Reg mark') => 'ASC',
        ];
    }

}