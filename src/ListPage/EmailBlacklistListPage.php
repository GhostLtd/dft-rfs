<?php

namespace App\ListPage;

use Ghost\GovUkCoreBundle\ListPage\AbstractListPage;
use Ghost\GovUkCoreBundle\ListPage\Field\Simple;
use Ghost\GovUkCoreBundle\ListPage\Field\TextFilter;
use App\Repository\EmailBlacklistRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

class EmailBlacklistListPage extends AbstractListPage
{
    public function __construct(
        private readonly EmailBlacklistRepository $repository,
        FormFactoryInterface $formFactory,
        RouterInterface $router,
    ) {
        parent::__construct($formFactory, $router);
    }

    #[\Override]
    protected function getFieldsDefinition(): array
    {
        return [
            (new Simple('Date added', 'b.createdAt'))->sortable(),
            (new TextFilter('Email', 'b.email'))->sortable(),
            (new TextFilter('Reason', 'b.reason'))->sortable(),
        ];
    }

    #[\Override]
    protected function getQueryBuilder(): QueryBuilder
    {
        return $this->repository->createQueryBuilder('b')
            ->select('b');
    }

    #[\Override]
    protected function getDefaultOrder(): array
    {
        return [
            Simple::generateId('Date added') => 'DESC',
        ];
    }
}
