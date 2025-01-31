<?php

declare(strict_types=1);

namespace Lion\Route;

use Lion\Dependency\Injection\Container;
use Lion\Route\Attributes\Rules;
use Lion\Route\Exceptions\RulesException;
use Lion\Route\Kernel\Http;
use Phroute\Phroute\Exception\HttpMethodNotAllowedException;
use Phroute\Phroute\Exception\HttpRouteNotFoundException;
use Phroute\Phroute\HandlerResolverInterface;
use Phroute\Phroute\Route;
use Phroute\Phroute\RouteDataInterface;
use ReflectionException;
use ReflectionMethod;

/**
 * Is responsible for dispatching HTTP web routes
 *
 * @package Lion\Route
 *
 * @codeCoverageIgnore
 */
class Dispatcher
{
    /**
     * [Container to generate dependency injection]
     *
     * @var Container $container
     */
    private Container $container;

    /**
     * [Kernel for HTTP requests]
     *
     * @var Http $http
     */
    private Http $http;

    private array $staticRouteMap;

    private array $variableRouteData;

    private array $filters;

    /**
     * [Rules stored in their execution]
     *
     * @var array<int, string> $reflectionCache
     */
    private array $reflectionCache = [];

    private $matchedRoute;

    private HandlerResolverInterface|RouterResolver $handlerResolver;

    /**
     * Create a new route dispatcher
     *
     * @param Container $container [Container to generate dependency injection]
     * @param RouteDataInterface $data [Interface RouteDataInterface]
     */
    public function __construct(Container $container, RouteDataInterface $data)
    {
        $this->container = $container;

        $this->http = new Http($this->container);

        $this->staticRouteMap = $data->getStaticRoutes();

        $this->variableRouteData = $data->getVariableRoutes();

        $this->filters = $data->getFilters();

        $this->handlerResolver = new RouterResolver($this->container);
    }

    /**
     * Dispatches all rules defined by attributes in the method
     *
     * @param mixed $classInstance [Class instance]
     * @param string $methodName [Class method]
     *
     * @return void
     *
     * @throws RulesException
     * @throws ReflectionException
     */
    private function dispatchRules(mixed $classInstance, string $methodName): void
    {
        $cacheKey = get_class($classInstance) . '::' . $methodName;

        if (!isset($this->reflectionCache[$cacheKey])) {
            $this->reflectionCache[$cacheKey] = (new ReflectionMethod($classInstance, $methodName))
                ->getAttributes(Rules::class);
        }

        $attributes = $this->reflectionCache[$cacheKey];

        if (!empty($attributes)) {
            $this->http->validateRules($attributes[0]->newInstance()->getRules());
        }
    }

    /**
     * Dispatch a route for the given HTTP Method / URI
     *
     * @param string $httpMethod
     * @param string $uri
     *
     * @return mixed
     *
     * @throws HttpMethodNotAllowedException
     * @throws HttpRouteNotFoundException
     * @throws ReflectionException
     * @throws RulesException
     */
    public function dispatch(string $httpMethod, string $uri): mixed
    {
        list($handler, $filters, $vars) = $this->dispatchRoute($httpMethod, trim($uri, '/'));

        list($beforeFilter, $afterFilter) = $this->parseFilters($filters);

        if (($response = $this->dispatchFilters($beforeFilter)) !== null) {
            return $response;
        }

        $resolvedHandler = $this->handlerResolver->resolve($handler);

        if (is_array($resolvedHandler)) {
            $this->dispatchRules($resolvedHandler[0], $resolvedHandler[1]);

            return $this->container->callMethod($resolvedHandler[0], $resolvedHandler[1], $vars);
        }

        return $this->container->callCallback($resolvedHandler, $vars);
    }

    /**
     * Dispatch a route filter
     *
     * @param $filters
     * @param null $response
     *
     * @return mixed|null
     */
    private function dispatchFilters($filters, $response = null): mixed
    {
        while ($filter = array_shift($filters)) {
            $handler = $this->handlerResolver->resolve($filter);

            if (($filteredResponse = call_user_func($handler, $response)) !== null) {
                return $filteredResponse;
            }
        }

        return $response;
    }

    /**
     * Normalise the array filters attached to the route and merge with any
     * global filters
     *
     * @param $filters
     *
     * @return array
     */
    private function parseFilters($filters): array
    {
        $beforeFilter = array();
        $afterFilter = array();

        if (isset($filters[Route::BEFORE])) {
            $beforeFilter = array_intersect_key($this->filters, array_flip((array) $filters[Route::BEFORE]));
        }

        if (isset($filters[Route::AFTER])) {
            $afterFilter = array_intersect_key($this->filters, array_flip((array) $filters[Route::AFTER]));
        }

        return array($beforeFilter, $afterFilter);
    }

    /**
     * Perform the route dispatching. Check static routes first followed by
     * variable routes
     *
     * @param $httpMethod
     * @param $uri
     *
     * @return mixed
     *
     * @throws HttpMethodNotAllowedException
     * @throws HttpRouteNotFoundException
     */
    private function dispatchRoute($httpMethod, $uri): mixed
    {
        if (isset($this->staticRouteMap[$uri])) {
            return $this->dispatchStaticRoute($httpMethod, $uri);
        }

        return $this->dispatchVariableRoute($httpMethod, $uri);
    }

    /**
     * Handle the dispatching of static routes
     *
     * @param $httpMethod
     * @param $uri
     *
     * @return mixed
     *
     * @throws HttpMethodNotAllowedException
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
     * Check fallback routes: HEAD for GET requests followed by the ANY
     * attachment
     *
     * @param $routes
     * @param $httpMethod
     *
     * @throws HttpMethodNotAllowedException
     */
    private function checkFallbacks($routes, $httpMethod): mixed
    {
        $additional = array(Route::ANY);

        if ($httpMethod === Route::HEAD) {
            $additional[] = Route::GET;
        }

        foreach ($additional as $method) {
            if (isset($routes[$method])) {
                return $method;
            }
        }

        $this->matchedRoute = $routes;

        throw new HttpMethodNotAllowedException('Allow: ' . implode(', ', array_keys($routes)));
    }

    /**
     * Handle the dispatching of variable routes
     *
     * @param $httpMethod
     * @param $uri
     *
     * @throws HttpMethodNotAllowedException
     * @throws HttpRouteNotFoundException
     */
    private function dispatchVariableRoute($httpMethod, $uri): mixed
    {
        foreach ($this->variableRouteData as $data) {
            if (!preg_match($data['regex'], $uri, $matches)) {
                continue;
            }

            $count = count($matches);

            while (!isset($data['routeMap'][$count++]));

            $routes = $data['routeMap'][$count - 1];

            if (!isset($routes[$httpMethod])) {
                $httpMethod = $this->checkFallbacks($routes, $httpMethod);
            }

            foreach (array_values($routes[$httpMethod][2]) as $i => $varName) {
                if (!isset($matches[$i + 1]) || $matches[$i + 1] === '') {
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
