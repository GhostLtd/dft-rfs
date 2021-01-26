<?php

namespace App\ListPage\Domestic;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\ListPage\AbstractListPage;
use App\ListPage\Field;
use Doctrine\ORM\QueryBuilder;

class SurveyListPage extends AbstractListPage
{
    protected string $type;

    public function setType(string $type): self {
        $this->type = $type;
        return $this;
    }

    protected function getFieldsDefinition(): array
    {
        $stateChoices = array_combine(array_map(fn($x) => ucfirst($x), Survey::STATE_CHOICES), Survey::STATE_CHOICES);
        return [
            (new Field('User', 'u.username'))->sortable(),
            (new Field('Start date', 'x.surveyPeriodStart'))->sortable(),
            (new Field('Reg mark', 'x.registrationMark'))->sortable()->textFilterable(),
            (new Field('Status', 'x.state'))->sortable()->selectFilterable($stateChoices),
            (new Field('In possession', 'r.isInPossessionOfVehicle'))->sortable(),
            (new Field('Business nature', 'r.businessNature'))->sortable()->textFilterable(),
            (new Field('Employees', 'r.numberOfEmployees'))->sortable()->selectFilterable(SurveyResponse::EMPLOYEES_CHOICES),
        ];
    }

    protected function addToQueryBuilder(QueryBuilder $queryBuilder): QueryBuilder
    {
        return $queryBuilder
            ->select('x, r, u')
            ->leftJoin('x.response', 'r')
            ->leftJoin('x.passcodeUser', 'u')
            ->andWhere('x.isNorthernIreland = :isNorthernIreland');
    }

    protected function getExtraQueryParameters(): array
    {
        return ['isNorthernIreland' => $this->type === 'ni'];
    }

    protected function getEntityClass(): string
    {
        return Survey::class;
    }

    protected function getDefaultOrder(): array
    {
        return [
            'surveyPeriodStart' => 'DESC',
            'registrationMark' => 'ASC',
        ];
    }

}