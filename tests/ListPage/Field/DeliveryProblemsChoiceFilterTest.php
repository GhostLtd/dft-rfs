<?php

namespace App\Tests\ListPage\Field;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyNotification;
use App\ListPage\Field\DeliveryProblemsChoiceFilter;
use App\Tests\Support\DeliveryProblemSurveysTrait;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Runs the filter's conditions against the (SQLite) test database, so the DQL and JSON access are exercised for real.
 */
class DeliveryProblemsChoiceFilterTest extends KernelTestCase
{
    use DeliveryProblemSurveysTrait;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        static::getContainer()->get(DatabaseToolCollection::class)->get()->loadFixtures([]);
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->createDeliveryProblemSurveys($this->entityManager);
    }

    public static function choices(): array
    {
        return [
            'any' => [DeliveryProblemsChoiceFilter::CHOICE_ANY, ['AFTER1', 'AFTER2', 'MIXED', 'SENDFAIL1', 'SENDFAIL2']],
            'any, unseen' => [DeliveryProblemsChoiceFilter::CHOICE_ANY_UNSEEN, ['AFTER1', 'MIXED', 'SENDFAIL1']],
            'send-time' => [DeliveryProblemsChoiceFilter::CHOICE_SEND_TIME, ['MIXED', 'SENDFAIL1', 'SENDFAIL2']],
            'send-time, unseen' => [DeliveryProblemsChoiceFilter::CHOICE_SEND_TIME_UNSEEN, ['SENDFAIL1']],
            'after sending' => [DeliveryProblemsChoiceFilter::CHOICE_AFTER_SENDING, ['AFTER1', 'AFTER2', 'MIXED']],
            'after sending, unseen' => [DeliveryProblemsChoiceFilter::CHOICE_AFTER_SENDING_UNSEEN, ['AFTER1', 'MIXED']],
            'unknown choice: no filtering' => ['gibberish', ['AFTER1', 'AFTER2', 'MIXED', 'NONE', 'OK', 'SENDFAIL1', 'SENDFAIL2', 'TEMP']],
        ];
    }

    #[DataProvider('choices')]
    public function testFilter(string $choice, array $expectedRegMarks): void
    {
        $queryBuilder = $this->entityManager->getRepository(Survey::class)->createQueryBuilder('survey');
        (new DeliveryProblemsChoiceFilter('Delivery', SurveyNotification::class, 'survey'))->addFilterCondition($queryBuilder, $choice);

        $labels = array_map(fn(Survey $s) => $this->deliveryProblemLabel($s->getRegistrationMark()), $queryBuilder->getQuery()->getResult());
        sort($labels);

        self::assertSame($expectedRegMarks, $labels);
    }

    public function testFormChoices(): void
    {
        self::assertSame([
            'Any' => 'any',
            'Any (unseen)' => 'any-unseen',
            'Send-time' => 'send-time',
            'Send-time (unseen)' => 'send-time-unseen',
            'After sending' => 'after-sending',
            'After sending (unseen)' => 'after-sending-unseen',
        ], (new DeliveryProblemsChoiceFilter('Delivery', SurveyNotification::class, 'survey'))->getFormOptions()['choices']);
    }
}
