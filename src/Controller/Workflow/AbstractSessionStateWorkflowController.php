<?php

namespace App\Controller\Workflow;

use App\Workflow\FormWizardInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

abstract class AbstractSessionStateWorkflowController extends AbstractWorkflowController
{
    public function getSessionKey()
    {
        return "wizard." . get_class($this);
    }

    protected $session;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $log, SessionInterface $session)
    {
        parent::__construct($entityManager, $log);
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
