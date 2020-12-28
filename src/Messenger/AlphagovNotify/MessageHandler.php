<?php


namespace App\Messenger\AlphagovNotify;


use Alphagov\Notifications\Client;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class MessageHandler implements MessageHandlerInterface
{
    /**
     * @var Client
     */
    private $alphagovNotify;

    public function __construct(Client $alphagovNotify)
    {
        $this->alphagovNotify = $alphagovNotify;
    }

    public function __invoke(AbstractMessage $message)
    {
        switch(get_class($message)) {
            case Email::class :
                $this->alphagovNotify->sendEmail(...$message->getSendMethodParameters());
                break;

            case Sms::class :
                $this->alphagovNotify->sendSms(...$message->getSendMethodParameters());
                break;

            case Letter::class :
                $this->alphagovNotify->sendLetter(...$message->getSendMethodParameters());
                break;

            default :
                throw new \UnexpectedValueException(get_class($message));
        }
    }
}
