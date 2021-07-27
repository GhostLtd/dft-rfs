<?php

namespace App\ListPage\International;

use App\Entity\International\Survey;
use App\ListPage\AbstractListPage;
use App\ListPage\Field\ChoiceFilter;
use App\ListPage\Field\Simple;
use App\ListPage\Field\TextFilter;
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
            (new TextFilter('Ref. no.', 'survey.referenceNumber'))->sortable(),
            (new Simple('Start date', 'survey.surveyPeriodStart'))->sortable(),
            (new Simple('End date', 'survey.surveyPeriodEnd'))->sortable(),
            (new TextFilter('Business name', 'company.businessName'))->sortable(),
            (new ChoiceFilter('Status', 'survey.state', $stateChoices))->sortable(),
            (new ChoiceFilter("QA'd?", 'survey.qualityAssured', [
                'Yes' => true,
                'No' => false,
            ]))->sortable(),
            (new Simple('Reminders')),
            (new Simple('# Vehicles')),
            (new Simple('# Trips')),
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
            Simple::generateId('Start date') => 'DESC',
            Simple::generateId('Business name') => 'ASC',
        ];
    }
}