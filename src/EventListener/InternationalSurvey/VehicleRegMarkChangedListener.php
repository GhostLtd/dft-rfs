<?php


namespace App\EventListener\InternationalSurvey;


use App\Entity\International\Vehicle;
use App\Entity\PasscodeUser;
use App\Utility\RegistrationMarkHelper;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class VehicleRegMarkChangedListener
{
    private $session;
    private $security;
    private $translator;

    public function __construct(SessionInterface $session, Security $security, TranslatorInterface $translator)
    {
        $this->session = $session;
        $this->security = $security;
        $this->translator = $translator;
    }

    public function preUpdate(PreUpdateEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();
        if (!$entity instanceof Vehicle) {
            return;
        }

        // Only flash message for frontend user (not admin)
        if (!$this->security->isGranted(PasscodeUser::ROLE_INTERNATIONAL_SURVEY_USER)) {
            return;
        }

        if (($newValue = $eventArgs->getNewValue('registrationMark')) !== ($oldValue = $eventArgs->getOldValue('registrationMark')))
        {
            $this->session->getFlashBag()->add(NotificationBanner::FLASH_BAG_TYPE, new NotificationBanner(
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