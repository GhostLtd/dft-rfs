<?php

namespace App\ListPage\RoRo;

use App\Entity\RoRo\Survey;
use App\ListPage\AbstractListPage;
use App\ListPage\Field\ChoiceFilter;
use App\ListPage\Field\Simple;
use App\ListPage\Field\TextAndCodeFilter;
use App\ListPage\RoRo\Field\MonthYearFilter;
use App\Repository\RoRo\SurveyRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

class SurveyListPage extends AbstractListPage
{
    public function __construct(protected SurveyRepository $surveyRepository, FormFactoryInterface $formFactory, RouterInterface $router)
    {
        parent::__construct($formFactory, $router);
    }

    #[\Override]
    protected function getFieldsDefinition(): array
    {
        $stateChoices = array_combine(array_map(fn($x) => ucfirst($x), Survey::STATE_FILTER_CHOICES), Survey::STATE_FILTER_CHOICES);
        return [
            (new MonthYearFilter('Month', 'survey.surveyPeriodStart'))->sortable(),
            (new TextAndCodeFilter('Operator', 'operator.name', 'operator.code'))->sortable(),
            (new TextAndCodeFilter('UK port', 'ukPort.name', 'ukPort.code'))->sortable(),
            (new TextAndCodeFilter('Foreign port', 'foreignPort.name', 'foreignPort.code'))->sortable(),
            (new ChoiceFilter('Status', 'survey.state', $stateChoices))->sortable(),
        ];
    }

    #[\Override]
    protected function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->surveyRepository->createQueryBuilder('survey');
        return $queryBuilder
            ->select('survey, route, operator, ukPort, foreignPort')
            ->join('survey.route', 'route')
            ->join('survey.operator', 'operator')
            ->join('route.ukPort', 'ukPort')
            ->join('route.foreignPort', 'foreignPort');
    }

    #[\Override]
    protected function getDefaultOrder(): array
    {
        return [
            Simple::generateId('Month') => 'DESC',
            Simple::generateId('Operator') => 'ASC',
            Simple::generateId('UK port') => 'ASC',
            Simple::generateId('Foreign port') => 'ASC',
        ];
    }
}