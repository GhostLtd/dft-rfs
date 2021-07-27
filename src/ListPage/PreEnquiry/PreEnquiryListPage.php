<?php

namespace App\ListPage\PreEnquiry;

use App\Entity\International\Survey;
use App\ListPage\AbstractListPage;
use App\ListPage\Field\ChoiceFilter;
use App\ListPage\Field\Simple;
use App\ListPage\Field\TextFilter;
use App\Repository\PreEnquiry\PreEnquiryRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

class PreEnquiryListPage extends AbstractListPage
{
    private PreEnquiryRepository $repository;

    public function __construct(PreEnquiryRepository $repository, FormFactoryInterface $formFactory, RouterInterface $router)
    {
        parent::__construct($formFactory, $router);
        $this->repository = $repository;
    }

    protected function getFieldsDefinition(): array
    {
        $stateChoices = array_combine(array_map(fn($x) => ucfirst($x), Survey::STATE_CHOICES), Survey::STATE_CHOICES);
        return [
            (new TextFilter('Reference number', 'preEnquiry.referenceNumber'))->sortable(),
            (new TextFilter('Company name', 'company.businessName'))->sortable(),
            (new ChoiceFilter('Status', 'preEnquiry.state', $stateChoices))->sortable(),
            (new Simple('Reminders')),
        ];
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->repository->createQueryBuilder('preEnquiry');
        return $queryBuilder
            ->select('preEnquiry, response, user, company')
            ->leftJoin('preEnquiry.response', 'response')
            ->leftJoin('preEnquiry.passcodeUser', 'user')
            ->leftJoin('preEnquiry.company', 'company');
    }

    protected function getDefaultOrder(): array
    {
        return [
        ];
    }
}