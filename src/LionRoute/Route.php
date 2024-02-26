<?php

declare(strict_types=1);

namespace Lion\Route;

use Closure;
use DI\ContainerBuilder;
use Lion\DependencyInjection\Container;
use Lion\Route\Middleware;
use Lion\Route\Dispatcher;
use Phroute\Phroute\Exception\HttpMethodNotAllowedException;
use Phroute\Phroute\Exception\HttpRouteNotFoundException;
use Phroute\Phroute\RouteCollector;

/**
 * Class to define web routes
 *
 * @package Lion\Route
 */
class Route
{
    /**
     * [Constant to define any type of HTTP protocol]
     *
     * @const ANY
     */
	const ANY = 'ANY';

    /**
     * [Constant to define the HTTP POST protocol]
     *
     * @const POST
     */
	const POST = 'POST';

    /**
     * [Constant to define the HTTP GET protocol]
     *
     * @const GET
     */
	const GET = 'GET';

    /**
     * [Constant to define the HTTP PUT protocol]
     *
     * @const PUT
     */
	const PUT = 'PUT';

    /**
     * [Constant to define the HTTP DELETE protocol]
     *
     * @const DELETE
     */
	const DELETE = 'DELETE';

    /**
     * [Constant to define the HTTP HEAD protocol]
     *
     * @const HEAD
     */
	const HEAD = 'HEAD';

    /**
     * [Constant to define the HTTP OPTIONS protocol]
     *
     * @const OPTIONS
     */
	const OPTIONS = 'OPTIONS';

    /**
     * [Constant to define the HTTP PATCH protocol]
     *
     * @const PATCH
     */
	const PATCH = 'PATCH';

    /**
     * [Constant to define the 'prefix' property]
     *
     * @const PREFIX
     */
	private const PREFIX = 'prefix';

    /**
     * [Constant to define the 'after' property]
     *
     * @const PREFIX
     */
	private const AFTER = 'after';

    /**
     * [Constant to define the 'before' property]
     *
     * @const PREFIX
     */
	private const BEFORE = 'before';

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
     * @var array $routes
     */
	private static array $routes = [];

    /**
     * [Filter List]
     *
     * @var array $filters
     */
	private static array $filters = [];

    /**
     * [Current group]
     *
     * @var string $prefix
     */
	private static string $prefix = '';

	/**
     * Initialize router settings
     *
     * @param  int $index [Index from which the route is obtained]
     *
     * @return void
     */
	public static function init(int $index = 1): void
	{
		self::$uri = explode('?', $_SERVER['REQUEST_URI'] ?? '')[0];
		self::$index = $index;
		self::$router = new RouteCollector();
        self::$container = new Container();
	}

	/**
     * Run the defined route configuration
     *
     * @param  string $type [Function that is executed]
     * @param  string $uri [URI for HTTP route]
     * @param  array $function [Function that executes]
     * @param  array $options [Filter options]
     *
     * @return void
     */
	private static function executeRoute(string $type, string $uri, Closure|array $function, array $options): void
	{
		if (count($options) > 0) {
			$middleware = [self::BEFORE => $options[0]];

			if (isset($options[1])) {
				$middleware[self::AFTER] = $options[1];
			}

			self::$router->$type($uri, $function, $middleware);
		} else {
			self::$router->$type($uri, $function);
		}
	}

	/**
     * Add the defined routes to the router
     *
     * @param string $uri [URI for HTTP route]
     * @param string $method [HTTP protocol]
     * @param  array $function [Function that executes]
     * @param  array $options [Filter options]
     *
     * @return void
     */
	private static function addRoutes(string $uri, string $method, Closure|array $function, array $options): void
	{
		$newUri = str_replace("//", "/", (self::$prefix . $uri));
		$callback = is_array($function) ? false : (is_string($function) ? false : true);
		$request = !is_string($function) ? false : ['url' => $function];
		$controller = !is_array($function) ? false : ['name' => $function[0], 'function' => $function[1]];

		if (!isset(self::$routes[$newUri][$method])) {
			self::$routes[$newUri][$method] = [
				'filters' => [...self::$filters, ...$options],
				'handler' => [
					'controller' => $controller,
					'callback' => $callback,
					'request' => $request
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
				'callback' => $callback,
				'request' => $request
			];
		}
	}

	/**
     * Get all routes along with the configuration data of the defined routes
     *
     * @return array
     */
	public static function getFullRoutes(): array
	{
		return self::$routes;
	}

	/**
     * Get all routes captured with the router (phroute)
     *
     * @return array
     */
	public static function getRoutes(): array
	{
		return self::$router->getData()->getStaticRoutes();
	}

	/**
     * Get all filters captured with the router (phroute)
     *
     * @return array
     */
	public static function getFilters(): array
	{
		return self::$router->getData()->getFilters();
	}

	/**
     * Get all variables captured with the router (phroute)
     *
     * @return array
     */
	public static function getVariables(): array
	{
		return self::$router->getData()->getVariableRoutes();
	}

	/**
	 * Add the defined filters to the router
     *
     * @param array<Middleware> $filters [List of defined filters]
     *
     * @return void
	 * */
	public static function addMiddleware(array $filters): void
	{
		foreach ($filters as $middleware) {
            self::$router->filter($middleware->getMiddlewareName(), function() use ($middleware) {
                self::$container->injectDependenciesMethod(
                    self::$container->injectDependencies($middleware->newObject()),
                    $middleware->getMethodClass()
                );
            });
		}
	}

	/**
     * Dispatch the data obtained from the router in JSON format
     *
     * @return void
     */
	public static function dispatch(): void
	{
		try {
            $container = (new ContainerBuilder())->useAutowiring(true)->useAttributes(true)->build();
            $dispatch = new Dispatcher(self::$router->getData(), new RouterResolver($container), self::$container);

			$response = $dispatch->dispatch(
				$_SERVER['REQUEST_METHOD'],
				implode('/', array_slice(explode('/', self::$uri), self::$index))
			);

			die(json_encode($response));
		} catch (HttpRouteNotFoundException $e) {
			die(json_encode(['status' => 'route-error', 'message' => $e->getMessage()]));
		} catch (HttpMethodNotAllowedException $e) {
			die(json_encode(['status' => 'route-error', 'message' => $e->getMessage()]));
		}
	}

	/**
     * Function to declare a route with the HTTP GET protocol
     *
     * @param  string $uri [URI for HTTP route]
     * @param  array $function [Function that executes]
     * @param  array $options [Filter options]
     *
     * @return void
     */
	public static function get(string $uri, Closure|array $function, array $options = []): void
	{
		self::executeRoute(strtolower(self::GET), $uri, $function, $options);
		self::addRoutes($uri, self::GET, $function, $options);
	}

	/**
     * Function to declare a route with the HTTP POST protocol
     *
     * @param  string $uri [URI for HTTP route]
     * @param  array $function [Function that executes]
     * @param  array $options [Filter options]
     *
     * @return void
     */
	public static function post(string $uri, Closure|array $function, array $options = []): void
	{
		self::executeRoute(strtolower(self::POST), $uri, $function, $options);
		self::addRoutes($uri, self::POST, $function, $options);
	}

	/**
     * Function to declare a route with the HTTP PUT protocol
     *
     * @param  string $uri [URI for HTTP route]
     * @param  array $function [Function that executes]
     * @param  array $options [Filter options]
     *
     * @return void
     */
	public static function put(string $uri, Closure|array $function, array $options = []): void
	{
		self::executeRoute(strtolower(self::PUT), $uri, $function, $options);
		self::addRoutes($uri, self::PUT, $function, $options);
	}

	/**
     * Function to declare a route with the HTTP DELETE protocol
     *
     * @param  string $uri [URI for HTTP route]
     * @param  array $function [Function that executes]
     * @param  array $options [Filter options]
     *
     * @return void
     */
	public static function delete(string $uri, Closure|array $function, array $options = []): void
	{
		self::executeRoute(strtolower(self::DELETE), $uri, $function, $options);
		self::addRoutes($uri, self::DELETE, $function, $options);
	}

	/**
     * Function to declare a route with the HTTP HEAD protocol
     *
     * @param  string $uri [URI for HTTP route]
     * @param  array $function [Function that executes]
     * @param  array $options [Filter options]
     *
     * @return void
     */
	public static function head(string $uri, Closure|array $function, array $options = []): void
	{
		self::executeRoute(strtolower(self::HEAD), $uri, $function, $options);
		self::addRoutes($uri, self::HEAD, $function, $options);
	}

	/**
     * Function to declare a route with the HTTP OPTIONS protocol
     *
     * @param  string $uri [URI for HTTP route]
     * @param  array $function [Function that executes]
     * @param  array $options [Filter options]
     *
     * @return void
     */
	public static function options(string $uri, Closure|array $function, array $options = []): void
	{
		self::executeRoute(strtolower(self::OPTIONS), $uri, $function, $options);
		self::addRoutes($uri, self::OPTIONS, $function, $options);
	}

	/**
     * Function to declare a route with the HTTP PATCH protocol
     *
     * @param  string $uri [URI for HTTP route]
     * @param  array $function [Function that executes]
     * @param  array $options [Filter options]
     *
     * @return void
     */
	public static function patch(string $uri, Closure|array $function, array $options = []): void
	{
		self::executeRoute(strtolower(self::PATCH), $uri, $function, $options);
		self::addRoutes($uri, self::PATCH, $function, $options);
	}

    /**
     * Function to declare any route with HTTP protocols
     *
     * @param  string $uri [URI for HTTP route]
     * @param  array $function [Function that executes]
     * @param  array $options [Filter options]
     *
     * @return void
     */
	public static function any(string $uri, Closure|array $function, array $options = []): void
	{
		self::executeRoute(strtolower(self::ANY), $uri, $function, $options);
		self::addRoutes($uri, self::ANY, $function, $options);
	}

    /**
     * Function to declare any route with HTTP protocols or to define the
     * route with certain HTTP protocols
     *
     * @param array<string> $methods [List of HTTP protocols for routes]
     * @param  string $uri [URI for HTTP route]
     * @param  array $function [Function that executes]
     * @param  array $options [Filter options]
     *
     * @return void
     */
	public static function match(array $methods, string $uri, Closure|array $function, array $options = []): void
	{
		foreach ($methods as $method) {
			self::executeRoute(strtolower(trim($method)), $uri, $function, $options);
			self::addRoutes($uri, strtoupper(trim($method)), $function, $options);
		}
	}

	/**
     * Defines the group to group the defined routes
     *
     * @param  string $name [Route group name]
     * @param  Closure $closure [Function that executes]
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
     * @param  array $filters [Defined filters]
     * @param  Closure $closure [Function that executes]
     *
     * @return void
     */
	public static function middleware(array $filters, Closure $closure): void
	{
	    $originalFilters = self::$filters;
	    $parentFilters = self::$filters;
	    self::$filters = [];
	    $listMiddleware = [];
	    $count = count($filters);

	    if ($count === 1) {
	        self::$filters = [...self::$filters, ...$filters];
	        $listMiddleware = [self::BEFORE => $filters[0]];

	        array_unshift(self::$filters, ...$parentFilters);
		    self::$router->group($listMiddleware, $closure);
		    self::$filters = $originalFilters;
	    } elseif ($count === 2) {
	        self::$filters = [...self::$filters, ...$filters];
	        $listMiddleware = [self::BEFORE => $filters[0], self::AFTER => $filters[1]];

	        array_unshift(self::$filters, ...$parentFilters);
		    self::$router->group($listMiddleware, $closure);
		    self::$filters = $originalFilters;
	    } elseif ($count >= 3) {
	        self::$filters = [...self::$filters, $filters[0], $filters[1]];
	        $previousPrefix = self::$prefix;
	        self::$prefix .= "{$filters[2]}/";

	        $listMiddleware = [
	        	self::BEFORE => $filters[0],
	        	self::AFTER => $filters[1],
	            self::PREFIX => $filters[2]
	        ];

	        array_unshift(self::$filters, ...$parentFilters);
		    self::$router->group($listMiddleware, $closure);
		    self::$filters = $originalFilters;
		    self::$prefix = $previousPrefix;
	    }
	}
}
