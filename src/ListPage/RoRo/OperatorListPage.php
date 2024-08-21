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
    public function __construct(protected OperatorRepository $operatorRepository, FormFactoryInterface $formFactory, RouterInterface $router)
    {
        parent::__construct($formFactory, $router);
    }

    #[\Override]
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

    #[\Override]
    protected function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->operatorRepository->createQueryBuilder('operator');
        return $queryBuilder
            ->select('operator, route')
            ->leftJoin('operator.routes', 'route');
    }

    #[\Override]
    protected function getDefaultOrder(): array
    {
        return [
            Simple::generateId('Name') => 'ASC',
        ];
    }
}