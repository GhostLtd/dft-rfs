<?php

namespace App\ListPage\International;

use App\Entity\International\Survey;
use App\Entity\International\SurveyResponse;
use App\ListPage\AbstractListPage;
use App\ListPage\Field;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

class SurveyListPage extends AbstractListPage
{
    protected function getFieldsDefinition(): array
    {
        $stateChoices = array_combine(array_map(fn($x) => ucfirst($x), Survey::STATE_CHOICES), Survey::STATE_CHOICES);
        return [
            (new Field('User', 'u.username'))->sortable(),
            (new Field('Ref. no.', 'x.referenceNumber'))->sortable(),
            (new Field('Start date', 'x.surveyPeriodStart'))->sortable(),
            (new Field('Business name', 'c.businessName'))->textFilterable()->sortable(),
            (new Field('Status', 'x.state'))->sortable()->selectFilterable($stateChoices),
            (new Field('Business nature', 'r.businessNature'))->sortable()->textFilterable(),
            (new Field('Employees', 'r.numberOfEmployees'))->sortable()->selectFilterable(SurveyResponse::EMPLOYEES_CHOICES),
        ];
    }

    protected function addToQueryBuilder(QueryBuilder $queryBuilder): QueryBuilder
    {
        return $queryBuilder
            ->select('x, r, u, c')
            ->leftJoin('x.response', 'r')
            ->leftJoin('x.passcodeUser', 'u')
            ->leftJoin('x.company', 'c');
    }

    protected function getEntityClass(): string
    {
        return Survey::class;
    }

    protected function setDefaultOrder()
    {
        $this->order = 'surveyPeriodStart';
        $this->orderDirection = 'DESC';
    }

    protected function getDefaultOrder(): array
    {
        return [
            'surveyPeriodStart' => 'DESC',
            'businessName' => 'ASC',
        ];
    }
}