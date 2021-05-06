<?php

namespace App\ListPage\Domestic;

use App\Entity\Domestic\Survey;
use App\ListPage\AbstractListPage;
use App\ListPage\Field;
use App\Repository\Domestic\SurveyRepository;
use Doctrine\ORM\EntityManagerInterface;
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
            (new Field('User', 'user.username'))->sortable(),
            (new Field('Week', null)),
            (new Field('Start date', 'survey.surveyPeriodStart'))->sortable(),
            (new Field('Reg mark', 'survey.registrationMark'))->sortable()->textFilterable(),
            (new Field('Status', 'survey.state'))->sortable()->selectFilterable($stateChoices),
            (new Field('In possession', 'response.isInPossessionOfVehicle'))->sortable(),
            (new Field("Summ.\nDays", null)),
            (new Field("Stop\nDays", null)),
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
            Field::generateId('Start date') => 'DESC',
            Field::generateId('Reg mark') => 'ASC',
        ];
    }

}