<?php

namespace App\ListPage\International;

use App\Entity\International\Survey;
use App\ListPage\AbstractListPage;
use App\ListPage\Field;
use App\Repository\International\SurveyRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

class SurveyListPage extends AbstractListPage
{
    /**
     * @var SurveyRepository
     */
    private SurveyRepository $repository;

    public function __construct(SurveyRepository $repository, FormFactoryInterface $formFactory, RouterInterface $router)
    {
        parent::__construct($formFactory, $router);
        $this->repository = $repository;
    }

    protected function getFieldsDefinition(): array
    {
        $stateChoices = array_combine(array_map(fn($x) => ucfirst($x), Survey::STATE_CHOICES), Survey::STATE_CHOICES);
        return [
            (new Field('User', 'user.username'))->sortable(),
            (new Field('Ref. no.', 'survey.referenceNumber'))->sortable(),
            (new Field('Start date', 'survey.surveyPeriodStart'))->sortable(),
            (new Field('Business name', 'company.businessName'))->textFilterable()->sortable(),
            (new Field('Status', 'survey.state'))->sortable()->selectFilterable($stateChoices),
            (new Field('# Vehicles', null)),
            (new Field('# Trips', null)),
        ];
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->repository->createQueryBuilder('survey');
        return $queryBuilder
            ->select('survey, response, user, company, vehicle, trip')
            ->leftJoin('survey.response', 'response')
            ->leftJoin('survey.passcodeUser', 'user')
            ->leftJoin('survey.company', 'company')
            ->leftJoin('response.vehicles', 'vehicle')
            ->leftJoin('vehicle.trips', 'trip');
    }

    protected function getDefaultOrder(): array
    {
        return [
            'surveyPeriodStart' => 'DESC',
            'businessName' => 'ASC',
        ];
    }
}