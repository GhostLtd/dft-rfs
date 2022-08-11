<?php

namespace App\Controller\Workflow;

use App\Workflow\FormWizardManager;
use App\Workflow\FormWizardStateInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

abstract class AbstractSessionStateWorkflowController extends AbstractWorkflowController
{
    protected SessionInterface $session;

    public function getSessionKey(): string
    {
        return "wizard." . get_class($this);
    }

    public function __construct(FormWizardManager $formWizardManager, EntityManagerInterface $entityManager, LoggerInterface $log, RequestStack $requestStack)
    {
        parent::__construct($formWizardManager, $entityManager, $log);
        $this->session = $requestStack->getSession();
    }

    protected function setFormWizard(FormWizardStateInterface $formWizard)
    {
        $this->session->set($this->getSessionKey(), $formWizard);
    }

    protected function cleanUp()
    {
        $this->session->remove($this->getSessionKey());
    }
}
