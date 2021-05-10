<?php


namespace App\Messenger\AlphagovNotify;


use Alphagov\Notifications\Client;
use App\Utility\AlphagovNotify\TemplateReference;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class MessageHandler implements MessageHandlerInterface
{
    private Client $alphagovNotify;
    private EntityManagerInterface $entityManager;

    public function __construct(Client $alphagovNotify, EntityManagerInterface $entityManager)
    {
        $this->alphagovNotify = $alphagovNotify;
        $this->entityManager = $entityManager;
    }

    public function __invoke(AbstractMessage $message)
    {
        switch(get_class($message)) {
            case Email::class :
                $response = $this->alphagovNotify->sendEmail(...$message->getSendMethodParameters());
                break;

            case Sms::class :
                $response = $this->alphagovNotify->sendSms(...$message->getSendMethodParameters());
                break;

            case Letter::class :
                $response = $this->alphagovNotify->sendLetter(...$message->getSendMethodParameters());
                break;

            default :
                throw new \UnexpectedValueException(get_class($message));
        }

        if ($response) {
            $response['timestamp'] = (new \DateTime())->format('c');

            /** @var ApiResponseInterface $entity */
            $entity = $this->entityManager->find($message->getOriginatingEntityClass(), $message->getOriginatingEntityId());
            $templateReferenceReflection = new \ReflectionClass(TemplateReference::class);
            $templateReferenceConstant = array_search($message->getTemplateId(), $templateReferenceReflection->getConstants());

            $entity->setNotifyApiResponse($templateReferenceConstant, $response);
            $this->entityManager->flush();
        }
    }
}
