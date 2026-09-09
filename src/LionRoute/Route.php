<?php

declare(strict_types=1);

namespace Lion\Route;

use Closure;
use DI\DependencyException;
use DI\NotFoundException;
use Lion\Dependency\Injection\Container;
use Lion\Request\Http;
use Lion\Request\Response;
use Lion\Request\Status;
use Lion\Route\Exceptions\RulesException;
use Lion\Route\Interface\MiddlewareInterface;
use Phroute\Phroute\Exception\HttpMethodNotAllowedException;
use Phroute\Phroute\Exception\HttpRouteNotFoundException;
use Phroute\Phroute\RouteCollector;

/**
 * Class to define web routes.
 *
 * @package Lion\Route
 */
class Route
{
    /**
     * Defines the HTTP ANY method.
     */
    public const string ANY = 'ANY';

    /**
     * Defines the HTTP POST method.
     */
    public const string POST = 'POST';

    /**
     * Defines the HTTP GET method.
     */
    public const string GET = 'GET';

    /**
     * Defines the HTTP PUT method.
     */
    public const string PUT = 'PUT';

    /**
     * Defines the HTTP DELETE method.
     */
    public const string DELETE = 'DELETE';

    /**
     * Defines the HTTP HEAD method.
     */
    public const string HEAD = 'HEAD';

    /**
     * Defines the HTTP OPTIONS method.
     */
    public const string OPTIONS = 'OPTIONS';

    /**
     * Defines the HTTP PATCH method.
     */
    public const string PATCH = 'PATCH';

    /**
     * Defines the property for the prefix option.
     */
    public const string PREFIX = 'prefix';

    /**
     * Defines the property for the filter/middleware option.
     */
    private const string BEFORE = 'before';

    /**
     * Collector instance to register routes.
     *
     * @var RouteCollector $router
     */
    private static RouteCollector $router;

    /**
     * Container instance for dependency injection.
     *
     * @var Container $container
     */
    private static Container $container;

    /**
     * Response instance for sending output.
     *
     * @var Response $response
     */
    private static Response $response;

    /**
     * Stores the request URI.
     *
     * @var string $uri
     */
    private static string $uri = '';

    /**
     * Index position to slice the URI path.
     *
     * @var int $index
     */
    private static int $index = 1;

    /**
     * Stores full detailed route configurations.
     *
     * @var array $routes
     */
    private static array $routes = [];

    /**
     * Active stack of filters/middlewares.
     *
     * @var array $filters
     */
    private static array $filters = [];

    /**
     * Current active URI prefix.
     *
     * @var string $prefix
     */
    private static string $prefix = '';

    /**
     * Current active controller class name.
     *
     * @var string $controller
     */
    private static string $controller = '';

    /**
     * Initialize router settings.
     *
     * @param int $index Index to trim the request URI path.
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
     * Build the resource to nest a controller to a route group.
     *
     * @param Closure|array<int, string>|string $function Callback, controller array or method name string.
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
     * Helper to register a route dynamically and reduce boilerplate.
     *
     * @param string $method HTTP method name.
     * @param string $uri Route path.
     * @param Closure|array<int, string>|string $function Handler callback or controller method.
     * @param array $options Additional options like middleware or prefix.
     *
     * @return void
     */
    private static function registerRoute(
        string $method,
        string $uri,
        Closure|array|string $function,
        array $options = []
    ): void {
        $build = self::buildResource($function);

        self::executeRoute(strtolower($method), $uri, $build, $options);
        self::addRoutes($uri, strtoupper($method), $build, $options);
    }

    /**
     * Run the defined route configuration.
     *
     * @param string $type HTTP method type.
     * @param string $uri Route URI.
     * @param Closure|array $function Handler callback or array.
     * @param array $options Middleware/Filter options.
     *
     * @return void
     */
    private static function executeRoute(string $type, string $uri, Closure|array $function, array $options = []): void
    {
        if (empty($options)) {
            self::$router->$type($uri, $function);

            return;
        }

        unset($options['prefix']);

        self::middleware($options, static function () use ($type, $uri, $function): void {
            self::$router->$type($uri, $function);
        });
    }

    /**
     * Add the defined routes to the internal tracking array.
     *
     * @param string $uri Route URI.
     * @param string $method HTTP Verb.
     * @param Closure|array $function Handler callback or array.
     * @param array $options Filters/Middlewares.
     *
     * @return void
     */
    private static function addRoutes(string $uri, string $method, Closure|array $function, array $options): void
    {
        $newUri = preg_replace('#/+#', '/', self::$prefix . $uri);
        $controller = !is_array($function) ? false : ['name' => $function[0], 'function' => $function[1]];

        $currentFilters = [...self::$filters, ...$options];
        $currentHandler = [
            'controller' => $controller,
            'callback' => is_callable($function),
        ];

        if (!isset(self::$routes[$newUri][$method])) {
            self::$routes[$newUri][$method] = [
                'filters' => $currentFilters,
                'handler' => $currentHandler,
            ];

            return;
        }

        self::$routes[$newUri][$method]['filters'] = [
            ...self::$routes[$newUri][$method]['filters'],
            ...$currentFilters,
        ];

        self::$routes[$newUri][$method]['handler'] = $currentHandler;
    }

    /**
     * Get all registered routes with full metadata.
     *
     * @return array
     */
    public static function getFullRoutes(): array
    {
        return self::$routes;
    }

    /**
     * Get routes registered directly in the RouteCollector instance.
     *
     * @return array
     */
    public static function getRoutes(): array
    {
        /** @var array<string, array<string, array<int, array<int|string, string>>>> $routes */
        $routes = self::$router->getData()->getStaticRoutes();

        return $routes;
    }

    /**
     * Get registered filters/middlewares from the RouteCollector instance.
     *
     * @return array
     */
    public static function getFilters(): array
    {
        /** @var array<string, array<int|string, string>> $filters */
        $filters = self::$router->getData()->getFilters();

        return $filters;
    }

    /**
     * Add defined filters/middlewares to the router collector.
     *
     * @param array $filters Array of filter aliases mapped to class names.
     *
     * @return void
     */
    public static function addMiddleware(array $filters): void
    {
        foreach ($filters as $middlewareName => $middlewareClass) {
            self::$router->filter($middlewareName, static function () use ($middlewareClass): void {
                /** @var MiddlewareInterface $middlewareInterface */
                $middlewareInterface = self::$container->resolve($middlewareClass);

                $middlewareInterface->process();
            });
        }
    }

    /**
     * Dispatch the router response and execute matching handler.
     *
     * @param string|null $method Optional custom HTTP method override.
     * @param string|null $customUri Optional custom URI path override.
     *
     * @return void
     *
     * @throws DependencyException Error while resolving the entry.
     * @throws NotFoundException No entry found for the given name.
     */
    public static function dispatch(?string $method = null, ?string $customUri = null): void
    {
        try {
            $requestMethod = $method ?? $_SERVER['REQUEST_METHOD'] ?? self::GET;

            $targetUri = $customUri ?? self::$uri;

            $dispatcher = new Dispatcher(self::$container, self::$router->getData());

            $uriPath = explode('/', $targetUri)
                    |> (fn($x) => array_slice($x, self::$index))
                    |> (fn($x) => implode('/', $x));

            $response = $dispatcher->dispatch($requestMethod, $uriPath);

            $noContentStatusCodes = [100, 101, 102, 103, 204, 205, 304];

            if (is_object($response) && !empty($response->code) && in_array($response->code, $noContentStatusCodes, true)) {
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
                self::$response->custom(Status::RULE_ERROR, $e->getMessage(), $e->getCode(), $e->getData())
            );
        }
    }

    /**
     * Register a GET route.
     *
     * @param string $uri Route path.
     * @param Closure|array|string $function Handler.
     * @param array $options Route options.
     *
     * @return void
     */
    public static function get(string $uri, Closure|array|string $function, array $options = []): void
    {
        self::registerRoute(self::GET, $uri, $function, $options);
    }

    /**
     * Register a POST route.
     *
     * @param string $uri Route path.
     * @param Closure|array|string $function Handler.
     * @param array $options Route options.
     *
     * @return void
     */
    public static function post(string $uri, Closure|array|string $function, array $options = []): void
    {
        self::registerRoute(self::POST, $uri, $function, $options);
    }

    /**
     * Register a PUT route.
     *
     * @param string $uri Route path.
     * @param Closure|array|string $function Handler.
     * @param array $options Route options.
     *
     * @return void
     */
    public static function put(string $uri, Closure|array|string $function, array $options = []): void
    {
        self::registerRoute(self::PUT, $uri, $function, $options);
    }

    /**
     * Register a DELETE route.
     *
     * @param string $uri Route path.
     * @param Closure|array|string $function Handler.
     * @param array $options Route options.
     *
     * @return void
     */
    public static function delete(string $uri, Closure|array|string $function, array $options = []): void
    {
        self::registerRoute(self::DELETE, $uri, $function, $options);
    }

    /**
     * Register a HEAD route.
     *
     * @param string $uri Route path.
     * @param Closure|array|string $function Handler.
     * @param array $options Route options.
     *
     * @return void
     */
    public static function head(string $uri, Closure|array|string $function, array $options = []): void
    {
        self::registerRoute(self::HEAD, $uri, $function, $options);
    }

    /**
     * Register an OPTIONS route.
     *
     * @param string $uri Route path.
     * @param Closure|array|string $function Handler.
     * @param array $options Route options.
     *
     * @return void
     */
    public static function options(string $uri, Closure|array|string $function, array $options = []): void
    {
        self::registerRoute(self::OPTIONS, $uri, $function, $options);
    }

    /**
     * Register a PATCH route.
     *
     * @param string $uri Route path.
     * @param Closure|array|string $function Handler.
     * @param array $options Route options.
     *
     * @return void
     */
    public static function patch(string $uri, Closure|array|string $function, array $options = []): void
    {
        self::registerRoute(self::PATCH, $uri, $function, $options);
    }

    /**
     * Register a route responding to ANY HTTP method.
     *
     * @param string $uri Route path.
     * @param Closure|array|string $function Handler.
     * @param array $options Route options.
     *
     * @return void
     */
    public static function any(string $uri, Closure|array|string $function, array $options = []): void
    {
        self::registerRoute(self::ANY, $uri, $function, $options);
    }

    /**
     * Register a route matching multiple specified HTTP methods.
     *
     * @param array $methods Array of HTTP methods.
     * @param string $uri Route path.
     * @param Closure|array|string $function Handler.
     * @param array $options Route options.
     *
     * @return void
     */
    public static function match(array $methods, string $uri, Closure|array|string $function, array $options = []): void
    {
        foreach ($methods as $method) {
            self::registerRoute(trim($method), $uri, $function, $options);
        }
    }

    /**
     * Group routes under a URI prefix.
     *
     * @param string $name Prefix name.
     * @param Closure $closure Group scope closure.
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
     * Attach middleware/filters to a group of routes.
     *
     * @param array $filters Middlewares or filters.
     * @param Closure $closure Group scope closure.
     *
     * @return void
     */
    public static function middleware(array $filters, Closure $closure): void
    {
        $originalFilters = self::$filters;

        $parentFilters = self::$filters;

        self::$filters = [];

        $createGroup = static function (array $filters, Closure $closure) use (&$createGroup): void {
            if (empty($filters)) {
                $closure();

                return;
            }

            self::$router->group(
                [self::BEFORE => array_shift($filters)],
                static function () use ($filters, $closure, $createGroup): void {
                    $createGroup($filters, $closure);
                }
            );
        };

        if (isset($filters['prefix'])) {
            $customPrefix = $filters['prefix'];

            unset($filters['prefix']);

            $previousPrefix = self::$prefix;

            self::$prefix .= "{$customPrefix}/";

            self::$filters = [...self::$filters, ...$filters];

            array_unshift(self::$filters, ...$parentFilters);

            self::$router->group(
                [self::PREFIX => $customPrefix],
                static function () use ($createGroup, $filters, $closure): void {
                    $createGroup($filters, $closure);
                }
            );

            self::$prefix = $previousPrefix;
        } else {
            self::$filters = [...self::$filters, ...$filters];

            array_unshift(self::$filters, ...$parentFilters);

            $createGroup($filters, $closure);
        }

        self::$filters = $originalFilters;
    }

    /**
     * Bind a controller class to a group of routes.
     *
     * @param string $controller Fully qualified controller class name.
     * @param Closure $closure Group scope closure.
     *
     * @return void
     */
    public static function controller(string $controller, Closure $closure): void
    {
        $previousController = self::$controller;

        self::$controller = $controller;

        $closure();

        self::$controller = $previousController;
    }
}
