<?php

namespace App\Controller\Workflow;

use App\Entity\DomesticSurvey;
use App\Workflow\DomesticSurveyVehicleAndBusinessDetailsState;
use App\Workflow\FormWizardInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

abstract class AbstractSessionStateWorkflowController extends AbstractWorkflowController
{
    public const SESSION_KEY = 'wizard.' . self::class;

    /**
     * @var SessionInterface
     */
    protected $session;

    public function __construct(EntityManagerInterface $entityManager, SessionInterface $session)
    {
        parent::__construct($entityManager);
        $this->session = $session;
    }

    protected function setFormWizard(FormWizardInterface $formWizard)
    {
        $this->session->set(self::SESSION_KEY, $formWizard);
    }

    protected function cleanUp()
    {
        $this->session->remove(self::SESSION_KEY);
    }
}
