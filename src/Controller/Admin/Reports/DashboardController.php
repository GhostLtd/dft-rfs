<?php

namespace App\Controller\Admin\Reports;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    /**
     * @Route("", name="dashboard")
     */
    public function dashboard(): Response
    {
        return $this->render('admin/report/dashboard.html.twig');
    }
}
