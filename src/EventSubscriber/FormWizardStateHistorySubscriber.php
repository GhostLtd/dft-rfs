<?php


namespace App\EventSubscriber;


use App\Workflow\FormWizardInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;

class FormWizardStateHistorySubscriber implements EventSubscriberInterface
{
    public function onEnter(Event $event)
    {
        $subject = $event->getSubject();
        if ($subject instanceof FormWizardInterface) {
            $subject->addStateToHistory($subject->getState());
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            'workflow.enter' => 'onEnter',
        ];
    }
}