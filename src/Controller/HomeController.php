<?php

namespace App\Controller;

use App\Repository\Domestic\SurveyRepository;
use App\Serializer\Encoder\SqlServerInsertEncoder;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\SQLSrv\Driver;
use Doctrine\DBAL\Query\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Serializer;

class HomeController extends AbstractController
{
    /**
     * @Route("/")
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        return $this->render('home/index.html.twig');
    }

    /**
     * @Route("/sitemap.txt")
     */
    public function sitemap()
    {
        return $this->render('home/sitemap.txt.twig');
    }

    /**
     * @Route("/testing")
     */
    public function test(SurveyRepository $surveyRepository, SqlServerInsertEncoder $sqlServerInsertEncoder)
    {

        $serializer = new Serializer([
            new DateTimeNormalizer([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d\TH:i:s'])
        ], [$sqlServerInsertEncoder]);
        $data = $surveyRepository->queryForExport();
        $norm = $serializer->normalize($data);
        $ser = $serializer->serialize($data, 'sql-server-insert');
        dump($ser);

//        $sqlDriver = new Driver();
//        $sqlDriver->createDatabasePlatformForVersion('9.00.1399');
//        $dbalConn = new Connection([], $sqlDriver);
//        $queryBuilder = new QueryBuilder($dbalConn);
//
//        dump($queryBuilder
//            ->insert('table_a')
//            ->values(array_combine(array_keys($norm[0]), array_fill(0, count($norm[0]), '?')))
//            ->setParameters(array_values($norm[0]))
////            ->getParameters()
//            ->getSQL());
//
//        sqlsrv_prepare()

        return new Response('<html></html>');
    }
}
