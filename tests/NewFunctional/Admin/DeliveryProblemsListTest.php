<?php

namespace App\Tests\NewFunctional\Admin;

use App\Tests\NewFunctional\AbstractFunctionalTestCase;
use App\Tests\Support\DeliveryProblemSurveysTrait;
use Doctrine\ORM\EntityManagerInterface;

/**
 * The "Delivery" column and filter on the CSRGT survey list (IRHS and pre-enquiry share the filter and Twig function)
 */
class DeliveryProblemsListTest extends AbstractFunctionalTestCase
{
    use DeliveryProblemSurveysTrait;

    protected function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([], [
            'hostname' => 'rfs-admin.localhost',
            'env' => ['TEST_GOOGLE_IAP_AUTH_USER' => 'test@example.com'],
        ]);
        $this->createDeliveryProblemSurveys(static::getContainer()->get(EntityManagerInterface::class));
    }

    /** @return array<string, string> survey label (see DeliveryProblemSurveysTrait) => Delivery cell text */
    private function deliveryCells(): array
    {
        $crawler = $this->client->getCrawler();
        $headers = $crawler->filter('table.list-table thead th')->each(fn($th) => $th->filter('select, input')->count() ? $th->filter('select, input')->attr('name') : trim($th->text()));
        $regMarkColumn = array_search('reg_mark', $headers, true);
        $deliveryColumn = array_search('delivery', $headers, true);
        self::assertNotFalse($regMarkColumn, 'Reg mark column');
        self::assertNotFalse($deliveryColumn, 'Delivery column');

        $cells = [];
        $crawler->filter('table.list-table tbody tr')->each(function ($tr) use (&$cells, $regMarkColumn, $deliveryColumn) {
            $tds = $tr->filter('td, th'); // in document order, whichever a row's cells are
            $cells[$this->deliveryProblemLabel(trim($tds->eq($regMarkColumn)->text()))] = trim($tds->eq($deliveryColumn)->text());
        });
        ksort($cells);

        return $cells;
    }

    public function testDeliveryColumnShowsProblemCounts(): void
    {
        $this->client->request('GET', '/csrgt/surveys-gb/');
        self::assertResponseIsSuccessful();

        self::assertSame([
            'AFTER1' => '1',
            'AFTER2' => '1',
            'MIXED' => '2',
            'NONE' => '—',
            'OK' => '—',
            'SENDFAIL1' => '1',
            'SENDFAIL2' => '1',
            'TEMP' => '—',
        ], $this->deliveryCells());

        // Red while any are unseen, green once all are seen
        self::assertCount(3, $this->client->getCrawler()->filter('table.list-table tbody .govuk-tag--red.delivery-problems'));
        self::assertCount(2, $this->client->getCrawler()->filter('table.list-table tbody .govuk-tag--green.delivery-problems'));
    }

    public function testFilterByUnseenProblems(): void
    {
        $this->client->request('GET', '/csrgt/surveys-gb/?delivery=any-unseen');
        self::assertResponseIsSuccessful();

        self::assertSame(['AFTER1', 'MIXED', 'SENDFAIL1'], array_keys($this->deliveryCells()));
    }

    public function testRegMarkDoesNotWrap(): void
    {
        $this->client->request('GET', '/csrgt/surveys-gb/');

        self::assertGreaterThan(0, $this->client->getCrawler()->filter('table.list-table tbody td.nowrap')->count());
    }

    public function testShortenedFilterLabels(): void
    {
        $this->client->request('GET', '/csrgt/surveys-gb/');
        $crawler = $this->client->getCrawler();

        self::assertSame(['Flags', 'Any', 'Unreviewed', 'Reviewed', 'None'], $crawler->filter('select[name="flags"] option')->each(fn($o) => $o->text()));
        self::assertSame(['Possession?', 'Yes', 'Yes (exempt)', 'On hire', 'Scrapped/stolen', 'Sold'], $crawler->filter('select[name="possession"] option')->each(fn($o) => $o->text()));
    }

    public function testOtherListsRender(): void
    {
        foreach (['/csrgt/surveys-ni/', '/irhs/surveys', '/pre-enquiry'] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseIsSuccessful($url);
            self::assertCount(1, $this->client->getCrawler()->filter('select[name="delivery"]'), $url);
        }
    }
}
