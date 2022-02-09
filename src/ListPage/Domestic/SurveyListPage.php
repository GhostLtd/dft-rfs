<?php

namespace App\ListPage\Domestic;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\ListPage\AbstractListPage;
use App\ListPage\Domestic\Field\DaysFilter;
use App\ListPage\Domestic\Field\WeekYearFilter;
use App\ListPage\Field\ChoiceFilter;
use App\ListPage\Field\QaChoiceFilter;
use App\ListPage\Field\Simple;
use App\ListPage\Field\SpaceStrippingTextFilter;
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
        $stateChoices = array_combine(array_map(fn($x) => ucfirst($x), Survey::STATE_FILTER_CHOICES), Survey::STATE_FILTER_CHOICES);
        $possessionChoices = array_combine(array_map(fn($x) => ucfirst($x), SurveyResponse::IN_POSSESSION_CHOICES), SurveyResponse::IN_POSSESSION_CHOICES);

        return [
            (new WeekYearFilter('Week', 'survey.surveyPeriodStart'))->sortable(),
            (new TextFilter('Company', 'survey.invitationAddress.line1'))->sortable(),
            (new SpaceStrippingTextFilter('Reg mark', 'survey.registrationMark'))->sortable(),
            (new ChoiceFilter('Status', 'survey.state', $stateChoices))->sortable(),
            (new QaChoiceFilter("QA'd?", 'survey.qualityAssured', [
                'Yes' => true,
                'No' => false,
            ]))->sortable(),
            (new ChoiceFilter('In possession?', 'response.isInPossessionOfVehicle', $possessionChoices))->sortable(),
            (new Simple('Reminders')),
            (new DaysFilter('Days')),
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
            Simple::generateId('Week') => 'DESC',
            Simple::generateId('Reg mark') => 'ASC',
        ];
    }

}