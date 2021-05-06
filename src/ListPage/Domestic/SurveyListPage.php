<?php

namespace App\ListPage\Domestic;

use App\Entity\Domestic\Survey;
use App\ListPage\AbstractListPage;
use App\ListPage\Field\ChoiceFilterable;
use App\ListPage\Field\Simple;
use App\ListPage\Field\TextFilterable;
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
            (new Simple('User', 'user.username'))->sortable(),
            (new Simple('Week')),
            (new Simple('Start date', 'survey.surveyPeriodStart'))->sortable(),
            (new TextFilterable('Reg mark', 'survey.registrationMark'))->sortable(),
            (new ChoiceFilterable('Status', 'survey.state', $stateChoices))->sortable(),
            (new Simple('In possession', 'response.isInPossessionOfVehicle'))->sortable(),
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