<?php

namespace App\ListPage;

use App\Entity\Utility\MaintenanceWarning;
use App\ListPage\Field\Simple;
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
            (new Simple('Date', 'maintenance_warning.start'))->sortable(),
            (new Simple('Start', 'maintenance_warning.start')),
            (new Simple('End', 'maintenance_warning.endTime')),
        ];
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->repository->createQueryBuilder('maintenance_warning');
        return $queryBuilder
            ->select('maintenance_warning')
            ->andWhere('maintenance_warning.start >= :now')
            ->setParameter('now', new \DateTime('-2 hours'));
    }

    protected function getDefaultOrder(): array
    {
        return [
            Simple::generateId('Date') => 'ASC',
        ];
    }
}