<?php

namespace App\ListPage;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;

abstract class AbstractListPage
{
    protected EntityManagerInterface $entityManager;
    protected FormFactoryInterface $formFactory;
    protected RouterInterface $router;

    protected ?int $page;
    protected ?array $formData;
    protected ?array $requestData;

    public function __construct(EntityManagerInterface $entityManager, FormFactoryInterface $formFactory, RouterInterface $router)
    {
        $this->entityManager = $entityManager;
        $this->formFactory = $formFactory;
        $this->router = $router;
    }

    /**
     * @return Field[]
     */
    abstract protected function getFieldsDefinition(): array;

    abstract protected function getEntityClass(): string;

    abstract protected function getPageRouteAndParameters(): array;

    public function getClearUrl(): string
    {
        return $this->getPageUrl(1, true);
    }

    public function getPageUrl(int $page, bool $excludeRequestData = false): string
    {
        [$route, $params] = $this->getPageRouteAndParameters();
        return $this->router->generate($route, array_merge(
            $params,
            ['page' => $page],
            $excludeRequestData ? [] : $this->requestData
        ));
    }

    public function getFields(): array {
        static $fields;

        if (!$fields) {
            $fields = $this->getFieldsDefinition();
        }

        return $fields;
    }

    public function handleRequest(Request $request): void
    {
        $this->page = intval($request->query->get('page', 1));

        if ($this->page < 1) {
            throw new NotFoundHttpException();
        }

        $form = $this->getFiltersForm()->handleRequest($request);
        $this->formData = array_filter($form->getData() ?? [], fn($x) => $x !== null);

        // Generate requestData from formData (essentially {regMark: 'wibble'} -> {filter: {regMark: 'wibble'}})
        $formName = $form->getName();
        $this->requestData = [];
        foreach($this->formData as $key => $value) {
            $name = "{$formName}[{$key}]";
            $this->requestData[$name] = $value;
        }
    }

    public function isClearClicked(): bool
    {
        $clear = $this->getFiltersForm()->get('buttons')->get('clear');
        return $clear instanceof SubmitButton && $clear->isClicked();
    }

    protected function getItemsPerPage(): int
    {
        return 5;
    }

    protected function addToQueryBuilder(QueryBuilder $queryBuilder): QueryBuilder
    {
        return $queryBuilder;
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        /** @var EntityRepository $repo */
        $repo = $this->entityManager->getRepository($this->getEntityClass());

        $qb = $this->addToQueryBuilder($repo->createQueryBuilder('x'));
        $parameters = [];

        foreach($this->getFields() as $field) {
            $parameter = $field->getParameterName();
            $fieldData = $this->formData[$parameter] ?? null;

            if ($fieldData !== null) {
                switch($field->getType()) {
                    case Field::TYPE_TEXT:
                        $qb = $qb->andWhere("{$field->getPropertyPath()} LIKE :{$parameter}");
                        $parameters[$parameter] = '%'.$fieldData.'%';
                        break;
                    case Field::TYPE_SELECT:
                        $qb = $qb->andWhere("{$field->getPropertyPath()} = :{$parameter}");
                        $parameters[$parameter] = $fieldData;
                        break;
                }
            }
        }

        return $qb->setParameters($parameters);
    }

    public function getFiltersForm(): FormInterface
    {
        static $form;

        if (!$form) {
            $form = $this->formFactory->create(ListPageForm::class, null, [
                'fields' => $this->getFields(),
            ]);
        }

        return $form;
    }

    public function getParameterNames(): array
    {
        static $parameterNames;

        if (!$parameterNames) {
            $parameterNames = [];
            foreach($this->getFields() as $field) {
                $parameterNames[$field->getLabel()] = $field->getParameterName();
            }
        }

        return $parameterNames;
    }

    public function getData(): ListPageData
    {
        $itemsPerPage = $this->getItemsPerPage();
        $page = $this->page;

        $queryBuilder = $this->getQueryBuilder()
            ->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage);

        $paginator = new Paginator($queryBuilder);
        $numRecords = $paginator->count();
        $numPages = ceil($numRecords / $itemsPerPage);

        if ($this->page > $numPages) {
            throw new NotFoundHttpException();
        }

        $paginationUrls = array_map(
            fn($page) => $page === '...' ? ['...', null] : [$page, $this->getPageUrl($page)],
            $this->generatePaginationPageList($page, $numPages)
        );

        $previousUrl = $page > 1 ? $this->getPageUrl($page - 1) : null;
        $nextUrl = $page < $numPages ? $this->getPageUrl($page + 1) : null;

        return new ListPageData($page, $numPages, $numRecords, $paginator->getQuery()->execute(), $nextUrl, $previousUrl, $paginationUrls, $this->getParameterNames());
    }

    protected function generatePaginationPageList(int $page, int $numPages, int $adjacents = 2): array
    {
        if ($numPages < 2) {
            return [];
        } else if ($numPages < 6 + ($adjacents * 2)) {
            return range(1, $numPages);
        } else if ($page < 1 + ($adjacents * 2)) {
            return array_merge(
                range(1, 1 + ($adjacents * 2)),
                ['...', $numPages]
            );
        } else if ($numPages + 1 - ($adjacents * 2) > $page && $page > ($adjacents * 2)) {
            return array_merge(
                [1, '...'],
                range($page - $adjacents, $page + $adjacents),
                ['...', $numPages]
            );
        } else {
            return array_merge(
                [1, '...'],
                range($numPages - ($adjacents * 2), $numPages)
            );
        }
    }
}