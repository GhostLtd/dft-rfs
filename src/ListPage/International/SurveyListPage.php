<?php

namespace App\ListPage\International;

use App\Entity\International\Action;
use App\Entity\International\Survey;
use Ghost\GovUkCoreBundle\ListPage\AbstractListPage;
use Ghost\GovUkCoreBundle\ListPage\Field\ChoiceFilter;
use Ghost\GovUkCoreBundle\ListPage\Field\DateTextFilter;
use App\ListPage\Field\QaChoiceFilter;
use Ghost\GovUkCoreBundle\ListPage\Field\Simple;
use Ghost\GovUkCoreBundle\ListPage\Field\TextFilter;
use App\Repository\International\SurveyRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

class SurveyListPage extends AbstractListPage
{
    public function __construct(private SurveyRepository $repository, FormFactoryInterface $formFactory, RouterInterface $router)
    {
        parent::__construct($formFactory, $router);
    }

    #[\Override]
    protected function getFieldsDefinition(): array
    {
        $stateChoices = array_combine(array_map(fn($x) => ucfirst($x), Survey::STATE_FILTER_CHOICES), Survey::STATE_FILTER_CHOICES);
        return [
            (new TextFilter('Ref. no.', 'survey.referenceNumber'))->sortable(),
            (new DateTextFilter('Start date', 'survey.surveyPeriodStart'))->sortable(),
            (new DateTextFilter('End date', 'survey.surveyPeriodEnd'))->sortable(),
            (new TextFilter('Business name', 'company.businessName'))->sortable(),
            (new ChoiceFilter('Status', 'survey.state', $stateChoices))->sortable(),
            (new QaChoiceFilter("QA'd?", 'survey.qualityAssured', [
                'Yes' => true,
                'No' => false,
            ]))->sortable(),
            (new Simple('Reminders')),
// Currently disabled, as it's thought that future rules will get too complex to implement in pure SQL
//            (new EmptiesChoiceFilter('Empties'))->sortable(),
            (new Simple('Empties')),
            (new Simple('# Vehicles')),
            (new Simple('# Trips')),
        ];
    }

    #[\Override]
    protected function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->repository->createQueryBuilder('survey');
        $queryBuilder
            ->select('survey, response, user, company, vehicle, trip')
            ->leftJoin('survey.response', 'response')
            ->leftJoin('survey.passcodeUser', 'user')
            ->leftJoin('survey.company', 'company')
            ->leftJoin('response.vehicles', 'vehicle')
            ->leftJoin('vehicle.trips', 'trip');

        $actionClass = Action::class;

        $sql = <<<SQL
(
    SELECT (COUNT(a2.id))
    FROM {$actionClass} a2
    JOIN a2.trip t2
    JOIN t2.vehicle v2
    WHERE v2.surveyResponse = response
    AND a2.loading = 1
    AND (a2.weightOfGoods IS NULL OR a2.weightOfGoods = 0)
) AS emptiesCount
SQL;

        $queryBuilder->addSelect($sql);

        return $queryBuilder;
    }

    #[\Override]
    protected function getDefaultOrder(): array
    {
        return [
            Simple::generateId('Start date') => 'DESC',
            Simple::generateId('Business name') => 'ASC',
        ];
    }
}