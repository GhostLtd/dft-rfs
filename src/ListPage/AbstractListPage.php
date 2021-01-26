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

    protected ?string $routeName;
    protected ?array $routeParams;

    protected ?string $order;
    protected ?string $orderDirection;

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


    protected function getExtraQueryParameters(): array
    {
        return [];
    }

    public function getClearUrl(): string
    {
        return $this->getPageUrl(1, true, true);
    }

    public function getPageUrl(int $page, bool $excludeRequestData = false, bool $excludeOrderData = false, array $extraData = []): string
    {
        $orderData = ($this->order && $this->orderDirection) ?
            ['orderBy' => $this->order, 'orderDirection' => $this->orderDirection] : [];

        return $this->router->generate($this->routeName, array_merge(
            $this->routeParams,
            $excludeRequestData ? [] : $this->requestData,
            $excludeOrderData ? [] : $orderData,
            $extraData,
            ['page' => $page],
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

        $this->routeName = $request->attributes->get('_route');
        $this->routeParams = $request->attributes->get('_route_params');

        $order = $request->query->get('orderBy', null);
        $orderDirection = $request->query->get('orderDirection', null);

        if ($order &&
            $orderDirection &&
            ($field = $this->getFieldByParameterName($order)) !== null &&
            $field->getSortable() &&
            in_array($orderDirection, ['ASC', 'DESC']))
        {
            $this->order = $order;
            $this->orderDirection = $orderDirection;
        } else {
            $this->order = null;
            $this->orderDirection = null;
        }

        $form = $this->getFiltersForm()->handleRequest($request);
        $this->formData = array_filter($form->getData() ?? [], fn($x) => $x !== null);
        $this->requestData = array_filter($this->formData, fn($key) => !in_array($key, ['orderBy', 'orderDirection']), ARRAY_FILTER_USE_KEY);
    }

    public function isClearClicked(): bool
    {
        $clear = $this->getFiltersForm()->get('clear');
        return $clear instanceof SubmitButton && $clear->isClicked();
    }

    protected function getItemsPerPage(): int
    {
        return 20;
    }

    protected function getDefaultOrder(): array
    {
        return [];
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

        if ($this->order && $this->orderDirection) {
            $field = $this->getFieldByParameterName($this->order);
            $qb->addOrderBy($field->getPropertyPath(), $this->orderDirection);
        } else {
            $defaultOrder = $this->getDefaultOrder();
            foreach ($defaultOrder as $parameterName=>$direction) {
                $field = $this->getFieldByParameterName($parameterName);
                $qb->addOrderBy($field->getPropertyPath(), $direction);
            }
        }

        return $qb->setParameters(array_merge($this->getExtraQueryParameters(), $parameters));
    }

    protected function getFieldByParameterName(string $parameterName): ?Field
    {
        $matchingFields = array_filter($this->getFields(), fn(Field $field) => $field->getParameterName() === $parameterName);
        return count($matchingFields) === 1 ? current($matchingFields) : null;
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

    public function getData(): ListPageData
    {
        $itemsPerPage = $this->getItemsPerPage();
        $page = $this->page;

        $queryBuilder = $this->getQueryBuilder()
            ->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage);

        $paginator = new Paginator($queryBuilder);
        $numRecords = $paginator->count();
        $numPages = intval(ceil($numRecords / $itemsPerPage));

        if ($numRecords !== 0 && $this->page > $numPages) {
            throw new NotFoundHttpException();
        }

        $paginationUrls = array_map(
            fn($page) => $page === '...' ? ['...', null] : [$page, $this->getPageUrl($page)],
            $this->generatePaginationPageList($page, $numPages)
        );

        $previousUrl = $page > 1 ? $this->getPageUrl($page - 1) : null;
        $nextUrl = $page < $numPages ? $this->getPageUrl($page + 1) : null;

        $orderUrlGenerator = function(string $order, string $orderDirection) {
            return ($order === $this->order && $orderDirection === $this->orderDirection) ?
                $this->getPageUrl(1, false, true) :
                $this->getPageUrl(1, false, true, ['orderBy' => $order, 'orderDirection' => $orderDirection]);
        };

        return new ListPageData(
            $page,
            $numPages,
            $numRecords,
            $paginator->getQuery()->execute(),
            $nextUrl,
            $previousUrl,
            $paginationUrls,
            $this->getFields(),
            $orderUrlGenerator,
            $this->order,
            $this->orderDirection,
        );
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