<?php

namespace App\Tests\Form\Type\Admin\RoRo;

use App\Entity\RoRo\Operator;
use App\Entity\Route\ForeignPort;
use App\Entity\Route\Route;
use App\Entity\Route\UkPort;
use App\Form\Admin\RoRo\OperatorRouteType;
use App\Repository\Route\RouteRepository;
use App\Tests\Form\Type\AbstractTypeTestCase;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\PreloadedExtension;

class OperatorRouteTypeTest extends AbstractTypeTestCase
{
    private const string AVAILABLE_ROUTE_ID = 'route-available';
    private const string ASSIGNED_ROUTE_ID = 'route-already-assigned';

    private Operator $operator;

    #[\Override]
    protected function setUp(): void
    {
        $this->operator = new Operator();
        $this->operator->addRoute($this->makeRoute(self::ASSIGNED_ROUTE_ID));
        parent::setUp();
    }

    private function makeRoute(string $id): Route
    {
        return (new Route())
            ->setId($id)
            ->setUkPort((new UkPort())->setName("uk-{$id}"))
            ->setForeignPort((new ForeignPort())->setName("fgn-{$id}"));
    }

    #[\Override]
    protected function getExtensions(): array
    {
        $availableRoute = $this->makeRoute(self::AVAILABLE_ROUTE_ID);
        $assignedRoute = $this->operator->getRoutes()->first();

        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn([$availableRoute, $assignedRoute]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('join')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        $routeRepository = $this->createMock(RouteRepository::class);
        $routeRepository->method('createQueryBuilder')->willReturn($queryBuilder);

        return array_merge(
            parent::getExtensions(),
            [
                new PreloadedExtension([new OperatorRouteType($routeRepository)], []),
            ],
        );
    }

    /**
     * @return array<string, string>
     */
    private function getChoiceValuesByRouteId(FormInterface $form): array
    {
        $view = $form->createView();
        $map = [];
        /** @var ChoiceView $choiceView */
        foreach ($view->children['route']->vars['choices'] as $choiceView) {
            /** @var Route $route */
            $route = $choiceView->data;
            $map[$route->getId()] = $choiceView->value;
        }
        return $map;
    }

    public function testAvailableRouteIsAccepted(): void
    {
        $form = $this->factory->create(OperatorRouteType::class, null, ['operator' => $this->operator]);
        $choiceValues = $this->getChoiceValuesByRouteId($form);

        $form->submit([
            'route' => $choiceValues[self::AVAILABLE_ROUTE_ID],
            'backdateSurveys' => 'false',
        ]);

        $this->assertTrue($form->isSynchronized());
        $this->assertCount(0, $form->get('route')->getErrors(), (string)$form->get('route')->getErrors());
        $this->assertTrue($form->isValid(), (string)$form->getErrors(true));
    }

    public function testAlreadyAssignedRouteIsRejected(): void
    {
        $form = $this->factory->create(OperatorRouteType::class, null, ['operator' => $this->operator]);
        $choiceValues = $this->getChoiceValuesByRouteId($form);

        $form->submit([
            'route' => $choiceValues[self::ASSIGNED_ROUTE_ID],
            'backdateSurveys' => 'false',
        ]);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());

        $routeErrors = $form->get('route')->getErrors();
        $this->assertCount(1, $routeErrors);
        $this->assertEquals('common.choice.invalid', $routeErrors[0]->getMessageTemplate());
    }

    public function testNoRouteIsRejected(): void
    {
        $form = $this->factory->create(OperatorRouteType::class, null, ['operator' => $this->operator]);

        $form->submit([
            'route' => '',
            'backdateSurveys' => 'false',
        ]);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertCount(1, $form->get('route')->getErrors());
    }
}
