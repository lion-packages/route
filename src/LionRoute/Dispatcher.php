<?php

declare(strict_types=1);

namespace Lion\Route;

use Lion\DependencyInjection\Container;
use Phroute\Phroute\Exception\HttpMethodNotAllowedException;
use Phroute\Phroute\Exception\HttpRouteNotFoundException;
use Phroute\Phroute\HandlerResolver;
use Phroute\Phroute\HandlerResolverInterface;
use Phroute\Phroute\Route;
use Phroute\Phroute\RouteDataInterface;

class Dispatcher
{
    private Container $container;
    private $staticRouteMap;
    private $variableRouteData;
    private $filters;
    private $handlerResolver;
    public $matchedRoute;

    /**
     * Create a new route dispatcher.
     *
     * @param RouteDataInterface $data
     * @param HandlerResolverInterface $resolver
     * @param Container $container
     */
    public function __construct(
        RouteDataInterface $data,
        HandlerResolverInterface $resolver = null,
        Container $container
    ) {
        $this->container = $container;
        $this->staticRouteMap = $data->getStaticRoutes();
        $this->variableRouteData = $data->getVariableRoutes();
        $this->filters = $data->getFilters();

        if ($resolver === null) {
            $this->handlerResolver = new HandlerResolver();
        } else {
            $this->handlerResolver = $resolver;
        }
    }

    /**
     * Dispatch a route for the given HTTP Method / URI.
     *
     * @param $httpMethod
     * @param $uri
     *
     * @return mixed|null
     */
    public function dispatch($httpMethod, $uri): mixed
    {
        list($handler, $filters, $vars) = $this->dispatchRoute($httpMethod, trim($uri, '/'));
        list($beforeFilter, $afterFilter) = $this->parseFilters($filters);

        if(($response = $this->dispatchFilters($beforeFilter)) !== null) {
            return $response;
        }

        $resolvedHandler = $this->handlerResolver->resolve($handler);

        if (is_array($resolvedHandler)) {
            return $this->container->injectDependenciesMethod(
                $this->container->injectDependencies(reset($resolvedHandler), $vars),
                end($resolvedHandler),
                $vars
            );
        }

        return $this->container->injectDependenciesCallback($resolvedHandler, $vars);
    }

    /**
     * Dispatch a route filter.
     *
     * @param $filters
     * @param null $response
     *
     * @return mixed|null
     */
    private function dispatchFilters($filters, $response = null): mixed
    {
        while($filter = array_shift($filters)) {
            $handler = $this->handlerResolver->resolve($filter);

            if(($filteredResponse = call_user_func($handler, $response)) !== null) {
                return $filteredResponse;
            }
        }

        return $response;
    }

    /**
     * Normalise the array filters attached to the route and merge with any global filters.
     *
     * @param $filters
     *
     * @return array
     */
    private function parseFilters($filters): array
    {
        $beforeFilter = array();
        $afterFilter = array();

        if(isset($filters[Route::BEFORE])) {
            $beforeFilter = array_intersect_key($this->filters, array_flip((array) $filters[Route::BEFORE]));
        }

        if(isset($filters[Route::AFTER])) {
            $afterFilter = array_intersect_key($this->filters, array_flip((array) $filters[Route::AFTER]));
        }

        return array($beforeFilter, $afterFilter);
    }

    /**
     * Perform the route dispatching. Check static routes first followed by variable routes.
     *
     * @param $httpMethod
     * @param $uri
     *
     * @throws Exception\HttpRouteNotFoundException
     */
    private function dispatchRoute($httpMethod, $uri)
    {
        if (isset($this->staticRouteMap[$uri])) {
            return $this->dispatchStaticRoute($httpMethod, $uri);
        }

        return $this->dispatchVariableRoute($httpMethod, $uri);
    }

    /**
     * Handle the dispatching of static routes.
     *
     * @param $httpMethod
     * @param $uri
     *
     * @return mixed
     *
     * @throws Exception\HttpMethodNotAllowedException
     */
    private function dispatchStaticRoute($httpMethod, $uri): mixed
    {
        $routes = $this->staticRouteMap[$uri];

        if (!isset($routes[$httpMethod])) {
            $httpMethod = $this->checkFallbacks($routes, $httpMethod);
        }

        return $routes[$httpMethod];
    }

    /**
     * Check fallback routes: HEAD for GET requests followed by the ANY attachment.
     *
     * @param $routes
     * @param $httpMethod
     *
     * @throws Exception\HttpMethodNotAllowedException
     */
    private function checkFallbacks($routes, $httpMethod): mixed
    {
        $additional = array(Route::ANY);

        if($httpMethod === Route::HEAD) {
            $additional[] = Route::GET;
        }

        foreach($additional as $method) {
            if(isset($routes[$method])) {
                return $method;
            }
        }

        $this->matchedRoute = $routes;

        throw new HttpMethodNotAllowedException('Allow: ' . implode(', ', array_keys($routes)));
    }

    /**
     * Handle the dispatching of variable routes.
     *
     * @param $httpMethod
     * @param $uri
     *
     * @throws Exception\HttpMethodNotAllowedException
     * @throws Exception\HttpRouteNotFoundException
     */
    private function dispatchVariableRoute($httpMethod, $uri): mixed
    {
        foreach ($this->variableRouteData as $data) {
            if (!preg_match($data['regex'], $uri, $matches)) {
                continue;
            }

            $count = count($matches);

            while(!isset($data['routeMap'][$count++]));

            $routes = $data['routeMap'][$count - 1];

            if (!isset($routes[$httpMethod])) {
                $httpMethod = $this->checkFallbacks($routes, $httpMethod);
            }

            foreach (array_values($routes[$httpMethod][2]) as $i => $varName) {
                if(!isset($matches[$i + 1]) || $matches[$i + 1] === '') {
                    unset($routes[$httpMethod][2][$varName]);
                } else {
                    $routes[$httpMethod][2][$varName] = $matches[$i + 1];
                }
            }

            return $routes[$httpMethod];
        }

        throw new HttpRouteNotFoundException('Route ' . $uri . ' does not exist');
    }
}
