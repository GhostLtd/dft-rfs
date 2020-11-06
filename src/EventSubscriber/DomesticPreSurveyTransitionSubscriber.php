<?php


namespace App\EventSubscriber;


use App\Entity\DomesticSurvey;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\TransitionBlocker;

class DomesticPreSurveyTransitionSubscriber implements EventSubscriberInterface
{

    public function guardTransition(GuardEvent $event)
    {
        /** @var DomesticSurvey $domesticSurvey */
        $domesticSurvey = $event->getSubject();

//        $choiceFormResult = dump($domesticSurvey->choiceFormResult);
//
//        $eventTransition = $event->getTransition();
//        $metadataResult = dump($event->getMetadata('result', $eventTransition));

//        if ($metadataResult !== $choiceFormResult)
//            $event->addTransitionBlocker(new TransitionBlocker('blocked' , '0'));
    }

    public static function getSubscribedEvents()
    {
        return [
//            'workflow.domestic_pre_survey.guard' => 'guardTransition',
        ];
    }
}