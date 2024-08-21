<?php

namespace App\Controller\Admin;

use App\Entity\SurveyInterface;
use App\Entity\SurveyStateInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Workflow\WorkflowInterface;

abstract class AbstractSurveyResendController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected WorkflowInterface $stateMachine,
    ) {}

    protected function doResend(Request $request, SurveyInterface $survey, string $formClass, string $templatePath): Response
    {
        $form = $this->createForm($formClass, $survey, ['is_resend' => true]);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $survey = $form->getData();

                if (!$survey instanceof SurveyInterface) {
                    throw new BadRequestHttpException();
                }

                $survey->setState(SurveyStateInterface::STATE_NEW);
                $survey->getPasscodeUser()->setPassword(null);

                $this->stateMachine->apply($survey, 'invite_user');
                $this->entityManager->flush();

                return $this->render($templatePath."/resend-success.html.twig", [
                    'survey' => $survey,
                ]);
            }
        }

        return $this->render($templatePath."/resend.html.twig", [
            'form' => $form,
        ]);
    }
}