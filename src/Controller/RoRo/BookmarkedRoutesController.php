<?php

namespace App\Controller\RoRo;

use App\Entity\RoRoUser;
use App\Form\ConfirmationType;
use App\Utility\RoRo\BookmarksHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route("/roro/bookmarked-routes")
 */
class BookmarkedRoutesController extends AbstractController
{
    public function __construct(protected EntityManagerInterface $entityManager, protected BookmarksHelper $bookmarksHelper)
    {}

    /**
     * @Route(name="app_roro_bookmarked_routes")
     */
    public function bookmarkedRoutes(Request $request, UserInterface $user): Response
    {
        if (!$user instanceof RoRoUser) {
            throw new NotFoundHttpException();
        }

        $mapping = $request->query->get('mapping', null);
        $userRoutes = $this->bookmarksHelper->getUserRoutes($user, $mapping);

        $form = $this->createForm(ConfirmationType::class, null, [
            'yes_label' => 'roro.bookmarked-routes.save',
            'no_label' => 'common.actions.cancel',
            'yes_disabled' => !$this->bookmarksHelper->wasChanged(),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $cancel = $form->get('no');
            if ($cancel instanceof SubmitButton && $cancel->isClicked()) {
                return $this->redirectToRoute('app_roro_dashboard');
            }

            if ($form->isValid()) {
                if ($this->bookmarksHelper->wasChanged()) {
                    foreach ($user->getBookmarkedRoutes() as $route) {
                        $userRoute = $userRoutes[$route->getId()] ?? null;

                        if ($userRoute && !$userRoute->isBookmarked()) {
                            $user->removeBookmarkedRoute($route);
                        }
                    }

                    foreach($userRoutes as $userRoute) {
                        if ($userRoute->isBookmarked()) {
                            $user->addBookmarkedRoute($userRoute->getRoute());
                        }
                    }

                    $this->entityManager->flush();
                }

                return $this->redirectToRoute('app_roro_dashboard');
            }
        }

        return $this->render('roro/bookmarked_routes.html.twig', [
            'breadcrumbToggle' => 'bookmarked-routes',
            'form' => $form->createView(),
            'routes' => $userRoutes,
            'toggleMappings' => $this->bookmarksHelper->getToggleMappings(),
            'user' => $user,
        ]);
    }
}
