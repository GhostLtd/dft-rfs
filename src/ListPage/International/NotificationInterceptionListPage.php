<?php

namespace App\ListPage\International;

use App\ListPage\AbstractListPage;
use App\ListPage\Field\Simple;
use App\ListPage\Field\TextFilter;
use App\Repository\International\NotificationInterceptionRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

class NotificationInterceptionListPage extends AbstractListPage
{
    protected bool $isNorthernIreland;

    public function __construct(private NotificationInterceptionRepository $repository, FormFactoryInterface $formFactory, RouterInterface $router)
    {
        parent::__construct($formFactory, $router);
    }

    #[\Override]
    protected function getFieldsDefinition(): array
    {
        return [
            (new TextFilter('Business name', 'ni.primaryName'))->sortable(),
            (new TextFilter('Other names', 'cn.name'))->sortable(),
            (new TextFilter('Email', 'ni.emails'))->sortable(),
        ];
    }

    #[\Override]
    protected function getQueryBuilder(): QueryBuilder
    {
        return $this->repository->createQueryBuilder('ni')->select('ni, cn')
            ->leftJoin('ni.additionalNames', 'cn');
    }

    #[\Override]
    protected function getDefaultOrder(): array
    {
        return [
            Simple::generateId('Business name') => 'ASC',
        ];
    }
}