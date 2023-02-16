<?php

namespace App\ListPage\RoRo;

use App\ListPage\AbstractListPage;
use App\ListPage\Field\QaChoiceFilter;
use App\ListPage\Field\Simple;
use App\ListPage\Field\TextFilter;
use App\Repository\RoRo\OperatorRepository;
use App\Repository\Route\RouteRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

class OperatorListPage extends AbstractListPage
{
    protected OperatorRepository $operatorRepository;

    public function __construct(OperatorRepository $operatorRepository, FormFactoryInterface $formFactory, RouterInterface $router)
    {
        parent::__construct($formFactory, $router);
        $this->operatorRepository = $operatorRepository;
    }

    protected function getFieldsDefinition(): array
    {
        return [
            (new TextFilter('Name', 'operator.name'))->sortable(),
            (new TextFilter('Code', 'operator.code'))->sortable(),
            (new QaChoiceFilter("Active?", 'operator.isActive', [
                'Yes' => true,
                'No' => false,
            ]))->sortable(),
        ];
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->operatorRepository->createQueryBuilder('operator');
        return $queryBuilder
            ->select('operator, route')
            ->leftJoin('operator.routes', 'route');
    }

    protected function getDefaultOrder(): array
    {
        return [
            Simple::generateId('Name') => 'ASC',
        ];
    }
}