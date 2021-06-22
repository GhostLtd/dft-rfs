<?php


namespace App\Tests\Messenger\AlphagovNotify;


use App\Entity\Address;
use App\Entity\Domestic\Survey;
use App\EventSubscriber\AlphagovNotifyMessengerSubscriber;
use App\Messenger\AlphagovNotify\Letter;
use App\Tests\DataFixtures\AlphagovNotify\MessageHandlerSurveyFixtures;
use App\Tests\Functional\AbstractFunctionalTest;
use App\Utility\AlphagovNotify\PersonalisationHelper;
use App\Utility\AlphagovNotify\Reference;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Messenger\MessageBusInterface;

class MessageHandlerTest extends AbstractFunctionalTest
{
    protected MessageBusInterface $bus;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::$kernel->getContainer()->get('test.service_container');
        $this->bus = $container->get('messenger.bus.default');
        $this->entityManager = $container->get(EntityManagerInterface::class);
        self::ensureKernelShutdown();
    }

    public function testSendLetterAccepted()
    {
        $survey = $this->getSurvey();

        $this->bus->dispatch($this->getInviteLetter($survey, $this->getValidTestAddress()));
        $this->runAsyncNotifyMessengerConsumer();

        $survey = $this->entityManager->find(get_class($survey), $survey->getId());
        $apiResponse = $survey->getNotifyApiResponse(Reference::EVENT_INVITE, Letter::class);
        self::assertSame(Reference::STATUS_ACCEPTED, $apiResponse[AlphagovNotifyMessengerSubscriber::STATUS_KEY]);
    }

    public function testSendLetterValidationError()
    {
        $survey = $this->getSurvey();

        $this->bus->dispatch($this->getInviteLetter($survey, $this->getInvalidTestAddress()));
        $this->runAsyncNotifyMessengerConsumer();

        $survey = $this->entityManager->find(get_class($survey), $survey->getId());
        $apiResponse = $survey->getNotifyApiResponse(Reference::EVENT_INVITE, Letter::class);
        self::assertSame(Reference::STATUS_FAILED, $apiResponse[AlphagovNotifyMessengerSubscriber::STATUS_KEY]);
    }

    protected function runAsyncNotifyMessengerConsumer()
    {
        $this->runCommand(
            'messenger:consume',
            [
                '--env' => 'test',
                // we only want to run a single message
                '--limit' => 1,
                // ensure that it doesn't run for too long
                '--time-limit' => 3,
                'receivers' => ['async_notify'],
            ]
        );
    }

    protected function getValidTestAddress(): Address
    {
        return (new Address())
            ->setLine1('Unit Test Road')
            ->setLine2('Unit Test Place')
            ->setPostcode('BA111HR');
    }

    protected function getInvalidTestAddress(): Address
    {
        return (new Address())
            ->setLine1('Unit Test Road')
            ->setLine2('Unit Test Place')
            ->setPostcode('AB123CD');
    }

    protected function getInviteLetter(Survey $survey, Address $address): Letter
    {
        return new Letter(
            Reference::EVENT_INVITE,
            get_class($survey),
            $survey->getId(),
            $address,
            Reference::LETTER_DOMESTIC_SURVEY_INVITE,
            PersonalisationHelper::getForDomesticSurvey($survey)
        );
    }

    protected function getSurvey(): Survey
    {
        $this->loadFixtures([MessageHandlerSurveyFixtures::class]);
        /** @var Survey $survey */
        $survey = $this->getFixtureByReference('survey:notify-message-handler');
        $survey->getPasscodeUser()->setPlainPassword('test');
        return $survey;
    }

    protected function runCommand($command, $options)
    {
        $application = new Application(static::$kernel);
        $application->setAutoExit(false);

        $options = array_merge(['command' => $command], $options);
        $input = new ArrayInput($options);
        $application->run($input, new NullOutput());
    }
}