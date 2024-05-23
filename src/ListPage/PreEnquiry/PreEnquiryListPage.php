<?php

namespace App\ListPage\PreEnquiry;

use App\Entity\PreEnquiry\PreEnquiry;
use App\ListPage\AbstractListPage;
use App\ListPage\Field\ChoiceFilter;
use App\ListPage\Field\DateTextFilter;
use App\ListPage\Field\Simple;
use App\ListPage\Field\TextFilter;
use App\Repository\PreEnquiry\PreEnquiryRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

class PreEnquiryListPage extends AbstractListPage
{
    public function __construct(
        protected PreEnquiryRepository $repository,
        FormFactoryInterface $formFactory,
        RouterInterface $router
    ) {
        parent::__construct($formFactory, $router);
    }

    #[\Override]
    protected function getFieldsDefinition(): array
    {
        $stateChoices = array_combine(array_map(fn($x) => ucfirst($x), PreEnquiry::STATE_FILTER_CHOICES), PreEnquiry::STATE_FILTER_CHOICES);
        return [
            (new TextFilter('Reference number', 'preEnquiry.referenceNumber'))->sortable(),
            (new DateTextFilter('Dispatch date', 'preEnquiry.dispatchDate'))->sortable(),
            (new TextFilter('Company name', 'preEnquiry.companyName'))->sortable(),
            (new ChoiceFilter('Status', 'preEnquiry.state', $stateChoices))->sortable(),
            (new Simple('Reminders')),
        ];
    }

    #[\Override]
    protected function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->repository->createQueryBuilder('preEnquiry');
        return $queryBuilder
            ->select('preEnquiry, response, user')
            ->leftJoin('preEnquiry.response', 'response')
            ->leftJoin('preEnquiry.passcodeUser', 'user');
    }

    #[\Override]
    protected function getDefaultOrder(): array
    {
        return [
            Simple::generateId('Dispatch date') => 'DESC',
        ];
    }
}
