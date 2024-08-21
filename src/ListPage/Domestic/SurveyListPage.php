<?php

namespace App\ListPage\Domestic;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\ListPage\AbstractListPage;
use App\ListPage\Domestic\Field\DaysFilter;
use App\ListPage\Domestic\Field\InPossessionFilter;
use App\ListPage\Domestic\Field\WeekYearFilter;
use App\ListPage\Field\ChoiceFilter;
use App\ListPage\Field\QaChoiceFilter;
use App\ListPage\Field\Simple;
use App\ListPage\Field\SpaceStrippingTextFilter;
use App\ListPage\Field\TextFilter;
use App\Repository\Domestic\SurveyRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

class SurveyListPage extends AbstractListPage
{
    protected bool $isNorthernIreland;

    public function __construct(protected SurveyRepository $repository, FormFactoryInterface $formFactory, RouterInterface $router)
    {
        parent::__construct($formFactory, $router);
    }

    public function setIsNorthernIreland(bool $isNorthernIreland): self
    {
        $this->isNorthernIreland = $isNorthernIreland;
        return $this;
    }

    #[\Override]
    protected function getFieldsDefinition(): array
    {
        $stateChoices = array_combine(array_map(fn($x) => ucfirst($x), Survey::STATE_FILTER_CHOICES), Survey::STATE_FILTER_CHOICES);
        $possessionChoices = array_combine(array_map(fn($x) => ucfirst($x), SurveyResponse::IN_POSSESSION_CHOICES), SurveyResponse::IN_POSSESSION_CHOICES);

        // Sneak "yes - exempt" in as the second option
        $possessionChoices = array_merge(
            array_slice($possessionChoices, 0, 1),
            [SurveyResponse::IN_POSSESSION_TRANSLATION_PREFIX . 'yes-exempt' => 'yes-exempt'],
            array_slice($possessionChoices, 1),
        );

        return [
            (new WeekYearFilter('Week', 'survey.surveyPeriodStart'))->sortable(),
            (new TextFilter('Company', 'survey.invitationAddress.line1'))->sortable(),
            (new SpaceStrippingTextFilter('Reg mark', 'survey.registrationMark'))->sortable(),
            (new ChoiceFilter('Status', 'survey.state', $stateChoices))->sortable(),
            (new QaChoiceFilter("QA'd?", 'survey.qualityAssured', [
                'Yes' => true,
                'No' => false,
            ]))->sortable(),
            (new InPossessionFilter('In possession?', 'response.isInPossessionOfVehicle', $possessionChoices))->sortable(),
            (new Simple('Reminders')),
            (new DaysFilter('Days')),
        ];
    }

    #[\Override]
    protected function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->repository->createQueryBuilder('survey');
        return $queryBuilder
            ->select('survey, response, user, day, stop, summary, reissuedSurvey')
            ->leftJoin('survey.response', 'response')
            ->leftJoin('survey.passcodeUser', 'user')
            ->leftJoin('response.days', 'day')
            ->leftJoin('day.stops', 'stop')
            ->leftJoin('day.summary', 'summary')

            // N.B. We don't have any choice but to join this, as it's a one-to-one and we're not on the owning
            //      side, so either we fetch here, or Doctrine will do another n separate fetches.
            //
            //      Additionally partial loads are now deprecated, so that's no longer a possibility.
            ->leftJoin('survey.reissuedSurvey', 'reissuedSurvey')

            ->andWhere('survey.isNorthernIreland = :isNorthernIreland')
            ->setParameter('isNorthernIreland', $this->isNorthernIreland);
    }

    #[\Override]
    protected function prePaginatorQueryAdjustments(Query $query): void
    {
        // Override these nasty fetch=EAGER flags causing extra fetches!
        $query->setFetchMode(Survey::class, "driverAvailability", ClassMetadata::FETCH_LAZY);
    }

    #[\Override]
    protected function getDefaultOrder(): array
    {
        return [
            Simple::generateId('Week') => 'DESC',
            Simple::generateId('Reg mark') => 'ASC',
        ];
    }
}