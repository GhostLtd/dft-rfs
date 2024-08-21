<?php

namespace App\Workflow;

use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FormWizardManager
{
    public const NOTIFICATION_BANNER_NORMALIZER_GROUP = 'form-wizard.notification-banner';

    public function __construct(
        protected FormFactoryInterface   $formFactory,
        protected RouterInterface        $router,
        protected EntityManagerInterface $entityManager,
        protected RequestStack           $requestStack,
        protected TranslatorInterface    $translator
    ) {}

    public function getFilteredTransitions(WorkflowInterface $stateMachine, FormWizardStateInterface $formWizard, FormInterface $form): array
    {
        $transitions = $stateMachine->getEnabledTransitions($formWizard);

        // Filter transitions based on form data (not accessible by guards)
        foreach ($transitions as $k => $v) {
            $metadata = $stateMachine->getMetadataStore()->getTransitionMetadata($v);

            if ($transitionWhenFormData = $metadata['transitionWhenFormData'] ?? false) {
                $data = $form->get($transitionWhenFormData['property'])->getData();
                $value = $transitionWhenFormData['value'];

                if (!(is_array($value) ? in_array($data, $value) : $data === $value)) {
                    unset($transitions[$k]);
                }
            }
        }

        return array_values($transitions);
    }

    public function createForm(FormWizardStateInterface $formWizard, WorkflowInterface $stateMachine, $showCancelButton = true): FormInterface
    {
        $state = $formWizard->getState();
        $formMap = $formWizard->getStateFormMap();

        if (isset($formMap[$state])) {
            $formClass = $formMap[$state];
            $formOptions = [];

            if (is_array($formClass)) {
                $formOptions = $formClass['options'];
                $formClass = $formClass['form'];
            }

            $form = $this->formFactory->create($formClass, $formWizard->getSubject(), $formOptions);
        } else {
            $form = $this->formFactory->createBuilder()->getForm();
        }

        // If we have only one possible transition, and it is meant to persist/flush
        // then we want to have a better label for the "continue" button
        $submitLabel = null;
        $cancelLabel = null;
        $transitions = $stateMachine->getEnabledTransitions($formWizard);
        if (count($transitions) === 1) {
            $transition = $transitions[array_key_last($transitions)];
            $metadata = $stateMachine->getMetadataStore()->getTransitionMetadata($transition);
            $isSavePoint = ($metadata['persist'] ?? false) === true;
            $submitLabel = $metadata['submitLabel'] ?? ($isSavePoint ? 'common.actions.save-and-continue' : null);
            $cancelLabel = $metadata['cancelLabel'] ?? null;
        }
        $form->add('continue', ButtonType::class, [
            'type' => 'submit',
            'label' => $submitLabel ?? 'common.actions.continue',
        ]);
        if ($showCancelButton) {
            $form->add('cancel', ButtonType::class, [
                'type' => 'submit',
                'label' => $cancelLabel ?? 'common.actions.cancel',
                'attr' => ['class' => 'govuk-button--secondary'],
            ]);
        }

        return $form;
    }

    public function processTransitionMetadata(array $transitionMetadata, $subject): ?string
    {
        if ($transitionMetadata['persist'] ?? false) {
            $this->persistSubject($subject);
        }

        if ($notificationBanner = $transitionMetadata['notificationBanner'] ?? false) {
            $this->handleNotificationBanners($notificationBanner, $subject);
        }

        $redirectUrl = null;
        if ($redirectRoute = $transitionMetadata['redirectRoute'] ?? false) {
            $redirectUrl = $this->resolveRedirectRouteForTransition($redirectRoute, $subject);
        }

        return $redirectUrl;
    }

    protected function persistSubject($subject): void
    {
        if (!$this->entityManager->contains($subject)) {
            $this->entityManager->persist($subject);
        };

        $this->entityManager->flush();
    }

    protected function handleNotificationBanners($notificationBanner, $subject): void
    {
        $classMetadataFactory = new ClassMetadataFactory(new AttributeLoader());
        $normalizer = new ObjectNormalizer($classMetadataFactory);

        if (($notificationBanner['title'] ?? false) && ($notificationBanner['heading'] ?? false) && ($notificationBanner['content'] ?? false)) {
            $transArgs = $normalizer->normalize($subject, null, ['groups' => self::NOTIFICATION_BANNER_NORMALIZER_GROUP]);
            $session = $this->requestStack->getSession();

            if ($session instanceof FlashBagAwareSessionInterface) {
                $session->getFlashBag()->add(NotificationBanner::FLASH_BAG_TYPE, new NotificationBanner(
                    $this->translator->trans($notificationBanner['title'], $transArgs),
                    $this->translator->trans($notificationBanner['heading'], $transArgs),
                    $this->translator->trans($notificationBanner['content'], $transArgs),
                    $notificationBanner['options'] ?? []
                ));
            }
        }
    }

    protected function resolveRedirectRouteForTransition($redirectRoute, $subject): string
    {
        if (is_array($redirectRoute)) {
            $propertyAccessor = PropertyAccess::createPropertyAccessor();

            // e.g. redirectRoute = ['routeName' => 'app_summary', 'parameterMappings' => ['id' => 'vehicleId']]
            // Would essentially call generateUrl('app_summary', ['id' => $subject->getVehicleId()]);

            $params = array_map(
                fn(string $propertyPath) => $propertyAccessor->getValue($subject, $propertyPath),
                $redirectRoute['parameterMappings'] ?? []
            );

            $routeName = $redirectRoute['routeName'];
        } else {
            $params = [];
            $routeName = $redirectRoute;
        }
        return $this->router->generate($routeName, $params);
    }
}
