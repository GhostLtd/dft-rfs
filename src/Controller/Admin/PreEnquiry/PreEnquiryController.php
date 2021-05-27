<?php

namespace App\Controller\Admin\PreEnquiry;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PreEnquiryController extends AbstractController
{
    private const ROUTE_PREFIX = 'admin_pre_enquiry_';
    public const ADD_ROUTE = self::ROUTE_PREFIX.'add';
}