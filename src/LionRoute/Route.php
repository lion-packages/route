<?php

declare(strict_types=1);

namespace Lion\Route;

use Closure;
use Lion\Dependency\Injection\Container;
use Lion\Request\Http;
use Lion\Request\Response;
use Lion\Request\Status;
use Lion\Route\Exceptions\RulesException;
use Lion\Route\Interface\MiddlewareInterface;
use Phroute\Phroute\Exception\HttpMethodNotAllowedException;
use Phroute\Phroute\Exception\HttpRouteNotFoundException;
use Phroute\Phroute\RouteCollector;
use ReflectionException;

/**
 * Class to define web routes
 *
 * @package Lion\Route
 */
class Route
{
    /**
     * [public constant to define any type of HTTP protocol]
     *
     * @public const ANY
     */
    public const string ANY = 'ANY';

    /**
     * [public constant to define the HTTP POST protocol]
     *
     * @public const POST
     */
    public const string POST = 'POST';

    /**
     * [public constant to define the HTTP GET protocol]
     *
     * @public const GET
     */
    public const string GET = 'GET';

    /**
     * [public constant to define the HTTP PUT protocol]
     *
     * @public const PUT
     */
    public const string PUT = 'PUT';

    /**
     * [public constant to define the HTTP DELETE protocol]
     *
     * @public const DELETE
     */
    public const string DELETE = 'DELETE';

    /**
     * [public constant to define the HTTP HEAD protocol]
     *
     * @public const HEAD
     */
    public const string HEAD = 'HEAD';

    /**
     * [public constant to define the HTTP OPTIONS protocol]
     *
     * @public const OPTIONS
     */
    public const string OPTIONS = 'OPTIONS';

    /**
     * [public constant to define the HTTP PATCH protocol]
     *
     * @public const PATCH
     */
    public const string PATCH = 'PATCH';

    /**
     * [public constant to define the 'prefix' property]
     *
     * @public const PREFIX
     */
    private const string PREFIX = 'prefix';

    /**
     * [public constant to define the 'before' property]
     *
     * @public const BEFORE
     */
    private const string BEFORE = 'before';

    /**
     * [Collector class object]
     *
     * @var RouteCollector $router
     */
    private static RouteCollector $router;

    /**
     * [Container class object]
     *
     * @var Container $container
     */
    private static Container $container;

    /**
     * [Allows you to manage custom or already defined response objects]
     *
     * @var Response $response
     */
    private static Response $response;

    /**
     * [Defines the URI]
     *
     * @var string $uri
     */
    private static string $uri;

    /**
     * [defines the Index from which the route is obtained]
     *
     * @var int $index
     */
    private static int $index;

    /**
     * [Route list]
     *
     * @var array<
     *     string,
     *     array<
     *         string,
     *         array<
     *             string,
     *             array<
     *                 int|string,
     *                 array<string, string>|bool|string
     *             >
     *         >
     *     >
     * > $routes
     */
    private static array $routes = [];

    /**
     * [Filter List]
     *
     * @var array<int|string, string> $filters
     */
    private static array $filters = [];

    /**
     * [Current group]
     *
     * @var string $prefix
     */
    private static string $prefix = '';

    /**
     * [Controller class]
     *
     * @var string $controller
     */
    private static string $controller = '';

    /**
     * Initialize router settings
     *
     * @param int $index [Index from which the route is obtained]
     *
     * @return void
     */
    public static function init(int $index = 1): void
    {
        self::$router = new RouteCollector();

        self::$container = new Container();

        self::$response = new Response();

        /** @var string $requestUri */
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';

        self::$uri = explode('?', $requestUri)[0];

        self::$index = $index;
    }

    /**
     * Build the resource to nest a controller to a route group
     *
     * @param Closure|array<int, string>|string $function [Resource to execute
     * the HTTP route, such as a function or a controller]
     *
     * @return Closure|array<int, string>
     */
    private static function buildResource(Closure|array|string $function): Closure|array
    {
        if (is_string($function)) {
            return [self::$controller, $function];
        }

        return $function;
    }

    /**
     * Run the defined route configuration
     *
     * @param string $type [Function that is executed]
     * @param string $uri [URI for HTTP route]
     * @param Closure|array<int, string> $function [Function that executes]
     * @param array<int|string, string> $options [Filter options]
     *
     * @return void
     */
    private static function executeRoute(string $type, string $uri, Closure|array $function, array $options = []): void
    {
        if (empty($options)) {
            self::$router->$type($uri, $function);
        } else {
            if (isset($options['prefix'])) {
                unset($options['prefix']);
            }

            self::middleware($options, function () use ($type, $uri, $function): void {
                self::$router->$type($uri, $function);
            });
        }
    }

    /**
     * Add the defined routes to the router
     *
     * @param string $uri [URI for HTTP route]
     * @param string $method [HTTP protocol]
     * @param Closure|array<int, string> $function [Function that executes]
     * @param array<int|string, string> $options [Filter options]
     *
     * @return void
     */
    private static function addRoutes(string $uri, string $method, Closure|array $function, array $options): void
    {
        $newUri = str_replace("//", "/", (self::$prefix . $uri));

        $controller = !is_array($function) ? false : ['name' => $function[0], 'function' => $function[1]];

        if (!isset(self::$routes[$newUri][$method])) {
            self::$routes[$newUri][$method] = [
                'filters' => [
                    ...self::$filters,
                    ...$options
                ],
                'handler' => [
                    'controller' => $controller,
                    'callback' => is_callable($function),
                ]
            ];
        } else {
            self::$routes[$newUri][$method]['filters'] = [
                ...self::$routes[$newUri][$method]['filters'],
                ...self::$filters,
                ...$options
            ];

            self::$routes[$newUri][$method]['handler'] = [
                'controller' => $controller,
                'callback' => is_callable($function),
            ];
        }
    }

    /**
     * Get all routes along with the configuration data of the defined routes
     *
     * @return array<string, array<string, array<string, array<int|string, array<string, string>|bool|string>>>>
     *
     * @codeCoverageIgnore
     */
    public static function getFullRoutes(): array
    {
        return self::$routes;
    }

    /**
     * Get all routes captured with the router (PHRoute)
     *
     * @return array<string, array<string, array<int, array<int|string, string>>>>
     *
     * @codeCoverageIgnore
     */
    public static function getRoutes(): array
    {
        /** @var array<string, array<string, array<int, array<int|string, string>>>> $routes */
        $routes = self::$router
            ->getData()
            ->getStaticRoutes();

        return $routes;
    }

    /**
     * Get all filters captured with the router (PHRoute)
     *
     * @return array<string, array<int|string, string>>
     */
    public static function getFilters(): array
    {
        /** @var array<string, array<int|string, string>> $filters */
        $filters = self::$router
            ->getData()
            ->getFilters();

        return $filters;
    }

    /**
     * Add the defined filters to the router
     *
     * @param array<string, class-string> $filters [List of defined filters]
     *
     * @return void
     */
    public static function addMiddleware(array $filters): void
    {
        foreach ($filters as $middlewareName => $middlewareClass) {
            self::$router->filter($middlewareName, function () use ($middlewareClass): void {
                /** @var MiddlewareInterface $middlewareInterface */
                $middlewareInterface = self::$container->resolve($middlewareClass);

                $middlewareInterface->process();
            });
        }
    }

    /**
     * Dispatch the data obtained from the router in JSON format
     *
     * @return void
     *
     * @codeCoverageIgnore
     */
    public static function dispatch(): void
    {
        try {
            /** @var string $requestMethod */
            $requestMethod = $_SERVER['REQUEST_METHOD'];

            $dispatcher = new Dispatcher(self::$container, self::$router->getData());

            $response = $dispatcher->dispatch(
                $requestMethod,
                implode('/', array_slice(explode('/', self::$uri), self::$index))
            );

            $noContentStatusCodes = [
                100, // Continue
                101, // Switching Protocols
                102, // Processing (WebDAV)
                103, // Early Hints
                204, // No Content
                205, // Reset Content
                304, // Not Modified
            ];

            if (is_object($response) && !empty($response->code) && in_array($response->code, $noContentStatusCodes)) {
                exit;
            }

            self::$response->finish($response);
        } catch (HttpRouteNotFoundException $e) {
            self::$response->finish(self::$response->custom(Status::ROUTE_ERROR, $e->getMessage(), Http::NOT_FOUND));
        } catch (HttpMethodNotAllowedException $e) {
            self::$response->finish(
                self::$response->custom(Status::ROUTE_ERROR, $e->getMessage(), Http::METHOD_NOT_ALLOWED)
            );
        } catch (RulesException $e) {
            self::$response->finish(
                self::$response->custom(Status::RULE_ERROR, $e->getMessage(), Http::INTERNAL_SERVER_ERROR)
            );
        } catch (ReflectionException $e) {
            self::$response->finish(
                self::$response->custom(Status::ERROR, $e->getMessage(), Http::INTERNAL_SERVER_ERROR)
            );
        }
    }

    /**
     * Function to declare a route with the HTTP GET protocol
     *
     * @param string $uri [URI for HTTP route]
     * @param Closure|array<int, string>|string $function [Resource to execute
     * the HTTP route, such as a function or a controller]
     * @param array<int|string, string> $options [Filter options]
     *
     * @return void
     */
    public static function get(string $uri, Closure|array|string $function, array $options = []): void
    {
        $build = self::buildResource($function);

        self::executeRoute(strtolower(self::GET), $uri, $build, $options);

        self::addRoutes($uri, self::GET, $build, $options);
    }

    /**
     * Function to declare a route with the HTTP POST protocol
     *
     * @param string $uri [URI for HTTP route]
     * @param Closure|array<int, string>|string $function [Resource to execute
     * the HTTP route, such as a function or a controller]
     * @param array<int|string, string> $options [Filter options]
     *
     * @return void
     */
    public static function post(string $uri, Closure|array|string $function, array $options = []): void
    {
        $build = self::buildResource($function);

        self::executeRoute(strtolower(self::POST), $uri, $build, $options);

        self::addRoutes($uri, self::POST, $build, $options);
    }

    /**
     * Function to declare a route with the HTTP PUT protocol
     *
     * @param string $uri [URI for HTTP route]
     * @param Closure|array<int, string>|string $function [Resource to execute
     * the HTTP route, such as a function or a controller]
     * @param array<int|string, string> $options [Filter options]
     *
     * @return void
     */
    public static function put(string $uri, Closure|array|string $function, array $options = []): void
    {
        $build = self::buildResource($function);

        self::executeRoute(strtolower(self::PUT), $uri, $build, $options);

        self::addRoutes($uri, self::PUT, $build, $options);
    }

    /**
     * Function to declare a route with the HTTP DELETE protocol
     *
     * @param string $uri [URI for HTTP route]
     * @param Closure|array<int, string>|string $function [Resource to execute
     * the HTTP route, such as a function or a controller]
     * @param array<int|string, string> $options [Filter options]
     *
     * @return void
     */
    public static function delete(string $uri, Closure|array|string $function, array $options = []): void
    {
        $build = self::buildResource($function);

        self::executeRoute(strtolower(self::DELETE), $uri, $build, $options);

        self::addRoutes($uri, self::DELETE, $build, $options);
    }

    /**
     * Function to declare a route with the HTTP HEAD protocol
     *
     * @param string $uri [URI for HTTP route]
     * @param Closure|array<int, string>|string $function [Resource to execute
     * the HTTP route, such as a function or a controller]
     * @param array<int|string, string> $options [Filter options]
     *
     * @return void
     */
    public static function head(string $uri, Closure|array|string $function, array $options = []): void
    {
        $build = self::buildResource($function);

        self::executeRoute(strtolower(self::HEAD), $uri, $build, $options);

        self::addRoutes($uri, self::HEAD, $build, $options);
    }

    /**
     * Function to declare a route with the HTTP OPTIONS protocol
     *
     * @param string $uri [URI for HTTP route]
     * @param Closure|array<int, string>|string $function [Resource to execute
     * the HTTP route, such as a function or a controller]
     * @param array<int|string, string> $options [Filter options]
     *
     * @return void
     */
    public static function options(string $uri, Closure|array|string $function, array $options = []): void
    {
        $build = self::buildResource($function);

        self::executeRoute(strtolower(self::OPTIONS), $uri, $build, $options);

        self::addRoutes($uri, self::OPTIONS, $build, $options);
    }

    /**
     * Function to declare a route with the HTTP PATCH protocol
     *
     * @param string $uri [URI for HTTP route]
     * @param Closure|array<int, string>|string $function [Resource to execute
     * the HTTP route, such as a function or a controller]
     * @param array<int|string, string> $options [Filter options]
     *
     * @return void
     */
    public static function patch(string $uri, Closure|array|string $function, array $options = []): void
    {
        $build = self::buildResource($function);

        self::executeRoute(strtolower(self::PATCH), $uri, $build, $options);

        self::addRoutes($uri, self::PATCH, $build, $options);
    }

    /**
     * Function to declare any route with HTTP protocols
     *
     * @param string $uri [URI for HTTP route]
     * @param Closure|array<int, string>|string $function [Resource to execute
     * the HTTP route, such as a function or a controller]
     * @param array<int|string, string> $options [Filter options]
     *
     * @return void
     */
    public static function any(string $uri, Closure|array|string $function, array $options = []): void
    {
        $build = self::buildResource($function);

        self::executeRoute(strtolower(self::ANY), $uri, $build, $options);

        self::addRoutes($uri, self::ANY, $build, $options);
    }

    /**
     * Function to declare any route with HTTP protocols or to define the
     * route with certain HTTP protocols
     *
     * @param array<int, string> $methods [List of HTTP protocols for routes]
     * @param string $uri [URI for HTTP route]
     * @param Closure|array<int, string>|string $function [Resource to execute
     * the HTTP route, such as a function or a controller]
     * @param array<int|string, string> $options [Filter options]
     *
     * @return void
     */
    public static function match(array $methods, string $uri, Closure|array|string $function, array $options = []): void
    {
        $build = self::buildResource($function);

        foreach ($methods as $method) {
            self::executeRoute(strtolower(trim($method)), $uri, $build, $options);

            self::addRoutes($uri, strtoupper(trim($method)), $build, $options);
        }
    }

    /**
     * Defines the group to group the defined routes
     *
     * @param string $name [Route group name]
     * @param Closure $closure [Function that executes]
     *
     * @return void
     */
    public static function prefix(string $name, Closure $closure): void
    {
        $previousPrefix = self::$prefix;

        self::$prefix .= "{$name}/";

        self::$router->group([self::PREFIX => $name], $closure);

        self::$prefix = $previousPrefix;
    }

    /**
     * Defines filters to group defined routes
     *
     * @param array<int|string, string> $filters [Defined filters]
     * @param Closure $closure [Function that executes]
     *
     * @return void
     */
    public static function middleware(array $filters, Closure $closure): void
    {
        $originalFilters = self::$filters;

        $parentFilters = self::$filters;

        self::$filters = [];

        $createGroup = function (array $filters, Closure $closure) use (&$createGroup): void {
            if (empty($filters)) {
                $closure();

                return;
            }

            self::$router->group(
                [self::BEFORE => array_shift($filters)],
                function () use ($filters, $closure, $createGroup): void {
                    $createGroup($filters, $closure);
                }
            );
        };

        if (isset($filters['prefix'])) {
            $customPrefix = $filters['prefix'];

            unset($filters['prefix']);

            $previousPrefix = self::$prefix;

            self::$prefix .= "{$customPrefix}/";

            self::$filters = [
                ...self::$filters,
                ...$filters
            ];

            array_unshift(self::$filters, ...$parentFilters);

            self::$router->group(
                [self::PREFIX => $customPrefix],
                function () use ($createGroup, $filters, $closure): void {
                    $createGroup($filters, $closure);
                }
            );

            self::$filters = $originalFilters;

            self::$prefix = $previousPrefix;
        } else {
            self::$filters = [
                ...self::$filters,
                ...$filters
            ];

            array_unshift(self::$filters, ...$parentFilters);

            $createGroup($filters, $closure);

            self::$filters = $originalFilters;
        }

        self::$filters = $originalFilters;
    }

    /**
     * Points to the HTTP routes controller class
     *
     * @param string $controller [Controller class]
     * @param Closure $closure [Function that executes]
     *
     * @return void
     */
    public static function controller(string $controller, Closure $closure): void
    {
        self::$controller = $controller;

        $closure();

        self::$controller = '';
    }
}
