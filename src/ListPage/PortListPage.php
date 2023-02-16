<?php

namespace App\ListPage;

use App\ListPage\Field\Simple;
use App\ListPage\Field\TextFilter;
use App\Repository\Route\ForeignPortRepository;
use App\Repository\Route\UkPortRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

class PortListPage extends AbstractListPage
{
    public const TYPE_PORT_FOREIGN = 'foreign';
    public const TYPE_PORT_UK = 'uk';

    protected ForeignPortRepository $foreignPortRepository;
    protected UkPortRepository $ukPortRepository;

    protected string $portType;

    public function __construct(ForeignPortRepository $foreignPortRepository, UkPortRepository $ukPortRepository, FormFactoryInterface $formFactory, RouterInterface $router)
    {
        parent::__construct($formFactory, $router);
        $this->foreignPortRepository = $foreignPortRepository;
        $this->ukPortRepository = $ukPortRepository;
    }

    public function setPortType(string $portType): self
    {
        $this->portType = $portType;
        return $this;
    }

    public function getPortType(): string
    {
        return $this->portType;
    }

    protected function getFieldsDefinition(): array
    {
        return [
            (new TextFilter('Name', 'port.name'))->sortable(),
            (new TextFilter('Code', 'port.code'))->sortable(),
        ];
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        $repository = match($this->portType) {
            self::TYPE_PORT_FOREIGN => $this->foreignPortRepository,
            self::TYPE_PORT_UK => $this->ukPortRepository,
        };

        return $repository->createQueryBuilder('port')
            ->select('port');
    }

    protected function getDefaultOrder(): array
    {
        return [
            Simple::generateId('Name') => 'ASC',
        ];
    }
}