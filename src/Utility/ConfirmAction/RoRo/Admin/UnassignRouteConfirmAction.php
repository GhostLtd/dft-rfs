<?php

namespace App\Utility\ConfirmAction\RoRo\Admin;

use App\Entity\RoRo\Operator;
use App\Entity\Route\Route;
use App\Utility\ConfirmAction\AbstractConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class UnassignRouteConfirmAction extends AbstractConfirmAction
{
    /** @var array{operator: Operator, route: Route} */
    protected $subject;

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, TranslatorInterface $translator, protected EntityManagerInterface $entityManager)
    {
        parent::__construct($formFactory, $requestStack, $translator);
    }

    #[\Override]
    public function getFormOptions(): array
    {
        return array_merge(parent::getFormOptions(), [
            'confirm_button_options' => [
                'attr' => ['class' => 'govuk-button--warning'],
            ],
        ]);
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
    public function doConfirmedAction($formData): void
    {
        $this->getOperator()->removeRoute($this->getRoute());
        $this->entityManager->flush();
    }
}