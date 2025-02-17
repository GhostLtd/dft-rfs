<?php

namespace App\ListPage;

use App\ListPage\Field\MultiFieldTextFilter;
use App\ListPage\Field\Simple;
use App\ListPage\Field\TextFilter;
use App\Repository\NotificationInterceptionRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

class NotificationInterceptionListPage extends AbstractListPage
{
    protected bool $isNorthernIreland;

    public function __construct(private readonly NotificationInterceptionRepository $repository, FormFactoryInterface $formFactory, RouterInterface $router)
    {
        parent::__construct($formFactory, $router);
    }

    #[\Override]
    protected function getFieldsDefinition(): array
    {
        return [
            (new TextFilter('Business names', 'cn.name'))->sortable(),
            (new MultiFieldTextFilter('Email', ['ni.csrgtEmails', 'ni.irhsEmails', 'ni.dvlaEmails'])),
        ];
    }

    #[\Override]
    protected function getQueryBuilder(): QueryBuilder
    {
        return $this->repository->createQueryBuilder('ni')->select('ni, cn')
            ->leftJoin('ni.names', 'cn');
    }

    #[\Override]
    protected function getDefaultOrder(): array
    {
        return [
            Simple::generateId('Business names') => 'ASC',
        ];
    }
}