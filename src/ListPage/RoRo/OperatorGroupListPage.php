<?php

namespace App\ListPage\RoRo;

use App\ListPage\AbstractListPage;
use App\ListPage\Field\QaChoiceFilter;
use App\ListPage\Field\Simple;
use App\ListPage\Field\TextFilter;
use App\Repository\RoRo\OperatorGroupRepository;
use App\Repository\RoRo\OperatorRepository;
use App\Repository\Route\RouteRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

class OperatorGroupListPage extends AbstractListPage
{
    public function __construct(
        protected OperatorGroupRepository $operatorGroupRepository,
        FormFactoryInterface              $formFactory,
        RouterInterface                   $router
    ) {
        parent::__construct($formFactory, $router);
    }

    protected function getFieldsDefinition(): array
    {
        return [
            (new TextFilter('Name', 'operatorGroup.name'))->sortable(),
        ];
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        return $this->operatorGroupRepository->createQueryBuilder('operatorGroup')->select('operatorGroup');
    }

    protected function getDefaultOrder(): array
    {
        return [
            Simple::generateId('Name') => 'ASC',
        ];
    }
}