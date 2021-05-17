<?php

namespace App\ListPage;

use App\Entity\Domestic\Survey;
use App\Entity\Utility\MaintenanceWarning;
use App\ListPage\AbstractListPage;
use App\ListPage\Domestic\Field\WeekYearFilter;
use App\ListPage\Field\ChoiceFilter;
use App\ListPage\Field\Simple;
use App\ListPage\Field\TextFilter;
use App\Repository\Domestic\SurveyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

class MaintenanceWarningListPage extends AbstractListPage
{
    private ObjectRepository $repository;

    public function __construct(EntityManagerInterface $entityManager, FormFactoryInterface $formFactory, RouterInterface $router)
    {
        parent::__construct($formFactory, $router);
        $this->repository = $entityManager->getRepository(MaintenanceWarning::class);
    }

    protected function getFieldsDefinition(): array
    {
        return [
            (new Simple('Start', 'maintenance_warning.start'))->sortable(),
            (new Simple('End', 'maintenance_warning.end'))->sortable(),
        ];
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->repository->createQueryBuilder('maintenance_warning');
        return $queryBuilder
            ->select('maintenance_warning')
            ->andWhere('maintenance_warning.end > :now')
            ->setParameter('now', new \DateTime());
    }

    protected function getDefaultOrder(): array
    {
        return [
            Simple::generateId('Start') => 'ASC',
        ];
    }
}