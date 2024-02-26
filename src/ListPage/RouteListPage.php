<?php

namespace App\ListPage;

use App\ListPage\Field\QaChoiceFilter;
use App\ListPage\Field\Simple;
use App\ListPage\Field\TextFilter;
use App\Repository\Route\RouteRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

class RouteListPage extends AbstractListPage
{
    public function __construct(protected RouteRepository $routeRepository, FormFactoryInterface $formFactory, RouterInterface $router)
    {
        parent::__construct($formFactory, $router);
    }

    #[\Override]
    protected function getFieldsDefinition(): array
    {
        return [
            (new TextFilter('UK Port', 'ukPort.name'))->sortable(),
            (new TextFilter('Foreign Port', 'foreignPort.name'))->sortable(),
            (new QaChoiceFilter("Active?", 'route.isActive', [
                'Yes' => true,
                'No' => false,
            ]))->sortable(),
        ];
    }

    #[\Override]
    protected function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->routeRepository->createQueryBuilder('route');
        return $queryBuilder
            ->select('route, ukPort, foreignPort')
            ->join('route.ukPort', 'ukPort')
            ->join('route.foreignPort', 'foreignPort');
    }

    #[\Override]
    protected function getDefaultOrder(): array
    {
        return [
            Simple::generateId('UK Port') => 'ASC',
            Simple::generateId('Foreign Port') => 'ASC',
        ];
    }
}