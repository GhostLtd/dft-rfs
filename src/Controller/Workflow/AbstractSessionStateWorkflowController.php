<?php

namespace App\Controller\Workflow;

use App\Entity\Domestic\Survey;
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
    public function getSessionKey()
    {
        return "wizard." . get_class($this);
    }

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
        $this->session->set($this->getSessionKey(), $formWizard);
    }

    protected function cleanUp()
    {
        $this->session->remove($this->getSessionKey());
    }
}
