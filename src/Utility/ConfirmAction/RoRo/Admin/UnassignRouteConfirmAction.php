<?php

namespace App\Utility\ConfirmAction\RoRo\Admin;

use App\Entity\RoRo\Operator;
use App\Entity\Route\Route;
use Ghost\GovUkCoreBundle\Utility\ConfirmAction\AbstractConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class UnassignRouteConfirmAction extends AbstractConfirmAction
{
    /** @var array{operator: Operator, route: Route} */
    protected mixed $subject;

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, protected EntityManagerInterface $entityManager)
    {
        parent::__construct($formFactory, $requestStack);
    }

    #[\Override]
    public function getFormOptions(): array
    {
        $options = parent::getFormOptions();
        $options['confirm_button_options']['attr'] = ['class' => 'govuk-button--warning'];
        return $options;
    }

    protected function getOperator(): Operator
    {
        return $this->subject['operator'];
    }

    protected function getRoute(): Route
    {
        return $this->subject['route'];
    }

    #[\Override]
    public function getTranslationParameters(): array
    {
        $operator = $this->getOperator();
        $route = $this->getRoute();

        return [
            'operator_name' => $operator->getName(),
            'uk_port_name' => $route->getUkPort()->getName(),
            'foreign_port_name' => $route->getForeignPort()->getName(),
        ];
    }

    #[\Override]
    public function getTranslationDomain(): ?string
    {
        return 'admin';
    }

    #[\Override]
    public function getTranslationKeyPrefix(): string
    {
        return 'operator.route.unassign';
    }

    #[\Override]
    public function doConfirmedAction(mixed $formData): void
    {
        $this->getOperator()->removeRoute($this->getRoute());
        $this->entityManager->flush();
    }
}