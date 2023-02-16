<?php

namespace App\Utility\RoRo;

use App\DTO\RoRo\UserRoute;
use App\Entity\RoRoUser;
use App\Repository\Route\RouteRepository;

class BookmarksHelper
{
    protected bool $changed;
    protected array $toggleMappings;

    public function __construct(protected RouteRepository $routeRepository)
    {}

    /**
     * @return array<UserRoute>
     */
    public function getUserRoutes(RoRoUser $user, ?string $mapping): array
    {
        $userRoutes = $this->routeRepository->getRoutesForUser($user);

        if ($mapping === null) {
            $mapping = array_filter(
                array_map(fn(UserRoute $userRoute) => $userRoute->isBookmarked() ? $userRoute->getRoute()->getId() : null, $userRoutes),
                fn(?string $id) => $id !== null
            );
        } else if (preg_match('/^[a-z0-9\-]+(,[a-z0-9\-]+)*$/i', $mapping)) {
            $mapping = explode(',', $mapping);
        } else {
            // Mapping query param was present, but empty (i.e. no bookmarks)
            $mapping = [];
        }

        $this->changed = false;
        $this->toggleMappings = [];

        $rearrangedUserRoutes = [];

        foreach($userRoutes as $userRoute) {
            $route = $userRoute->getRoute();
            $routeId = $route->getId();

            $isBookmarked = in_array($routeId, $mapping);

            if ($userRoute->isBookmarked() !== $isBookmarked) {
                $this->changed = true;
            }

            $rearrangedUserRoutes[$routeId] = new UserRoute($userRoute->getRoute(), $isBookmarked);

            // Build toggleMappings for each routeId, where a toggleMapping is "what query params would I end up with
            // if I toggled this particular route?" (i.e. to add or remove bookmarks).

            // The query params is a list of routeIds that are considered to be bookmarked.
            $toggleMapping = $mapping ?? [];
            if ($isBookmarked) {
                $toggleMapping = array_filter($toggleMapping, static fn(string $id) => $id !== $routeId);
            } else {
                $toggleMapping[] = $routeId;
            }
            $this->toggleMappings[$routeId] = join(',', $toggleMapping);
        }

        return $rearrangedUserRoutes;
    }

    public function wasChanged(): bool
    {
        return $this->changed;
    }

    /**
     * @return array{string: string}
     */
    public function getToggleMappings(): array
    {
        return $this->toggleMappings;
    }
}