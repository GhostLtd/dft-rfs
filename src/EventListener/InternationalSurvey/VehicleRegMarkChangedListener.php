<?php

namespace App\EventListener\InternationalSurvey;

use App\Entity\International\Vehicle;
use App\Entity\PasscodeUser;
use App\Utility\RegistrationMarkHelper;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class VehicleRegMarkChangedListener
{
    public function __construct(
        protected RequestStack        $requestStack,
        protected Security            $security,
        protected TranslatorInterface $translator
    ) {}

    public function preUpdate(PreUpdateEventArgs $eventArgs): void
    {
        $entity = $eventArgs->getObject();
        if (!$entity instanceof Vehicle) {
            return;
        }

        // Only flash message for frontend user (not admin)
        if (!$this->security->isGranted(PasscodeUser::ROLE_INTERNATIONAL_SURVEY_USER)) {
            return;
        };
        if ($eventArgs->hasChangedField('registrationMark')
            && ($newValue = $eventArgs->getNewValue('registrationMark')) !== ($oldValue = $eventArgs->getOldValue('registrationMark'))
        ) {
            $session = $this->requestStack->getSession();
            if ($session instanceof FlashBagAwareSessionInterface) {
                $session->getFlashBag()->add(NotificationBanner::FLASH_BAG_TYPE, new NotificationBanner(
                    'Check vehicle details',
                    'Vehicle registration mark has changed',
                    $this->translator->trans('international.vehicle.vehicle-details.registration-mark.changed-notification-content', [
                        'newValue' => (new RegistrationMarkHelper($newValue))->getFormattedRegistrationMark(),
                        'oldValue' => (new RegistrationMarkHelper($oldValue))->getFormattedRegistrationMark(),
                    ])
                ));
            }
        }
    }
}
