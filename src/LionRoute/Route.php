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

class Route
{
	const ANY = 'ANY';
	const POST = 'POST';
	const GET = 'GET';
	const PUT = 'PUT';
	const DELETE = 'DELETE';
	const HEAD = 'HEAD';
	const OPTIONS = 'OPTIONS';
	const PATCH = 'PATCH';

	private const PREFIX = 'prefix';
	private const AFTER = 'after';
	private const BEFORE = 'before';

	private static RouteCollector $router;
    private static Container $container;

	private static string $uri;
	private static int $index;
	private static array $routes = [];
	private static array $filters = [];
	private static string $prefix = '';

	/**
	 * Initialize router settings
	 * */
	public static function init(int $index = 1): void
	{
		self::$uri = explode('?', $_SERVER['REQUEST_URI'] ?? '')[0];
		self::$index = $index;
		self::$router = new RouteCollector();
        self::$container = new Container();
	}

	/**
	 * Run the defined route configuration
	 * */
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
	 * */
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
	 * */
	public static function getFullRoutes(): array
	{
		return self::$routes;
	}

	/**
	 * Get all routes captured with the router (phroute)
	 * */
	public static function getRoutes(): array
	{
		return self::$router->getData()->getStaticRoutes();
	}

	/**
	 * Get all filters captured with the router (phroute)
	 * */
	public static function getFilters(): array
	{
		return self::$router->getData()->getFilters();
	}

	/**
	 * Get all variables captured with the router (phroute)
	 * */
	public static function getVariables(): array
	{
		return self::$router->getData()->getVariableRoutes();
	}

	/**
	 * Add the defined filters to the router
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
	 * */
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
	 * */
	public static function get(string $uri, Closure|array $function, array $options = []): void
	{
		self::executeRoute(strtolower(self::GET), $uri, $function, $options);
		self::addRoutes($uri, self::GET, $function, $options);
	}

	/**
	 * Function to declare a route with the HTTP POST protocol
	 * */
	public static function post(string $uri, Closure|array $function, array $options = []): void
	{
		self::executeRoute(strtolower(self::POST), $uri, $function, $options);
		self::addRoutes($uri, self::POST, $function, $options);
	}

	/**
	 * Function to declare a route with the HTTP PUT protocol
	 * */
	public static function put(string $uri, Closure|array $function, array $options = []): void
	{
		self::executeRoute(strtolower(self::PUT), $uri, $function, $options);
		self::addRoutes($uri, self::PUT, $function, $options);
	}

	/**
	 * Function to declare a route with the HTTP DELETE protocol
	 * */
	public static function delete(string $uri, Closure|array $function, array $options = []): void
	{
		self::executeRoute(strtolower(self::DELETE), $uri, $function, $options);
		self::addRoutes($uri, self::DELETE, $function, $options);
	}

	/**
	 * Function to declare a route with the HTTP HEAD protocol
	 * */
	public static function head(string $uri, Closure|array $function, array $options = []): void
	{
		self::executeRoute(strtolower(self::HEAD), $uri, $function, $options);
		self::addRoutes($uri, self::HEAD, $function, $options);
	}

	/**
	 * Function to declare a route with the HTTP OPTIONS protocol
	 * */
	public static function options(string $uri, Closure|array $function, array $options = []): void
	{
		self::executeRoute(strtolower(self::OPTIONS), $uri, $function, $options);
		self::addRoutes($uri, self::OPTIONS, $function, $options);
	}

	/**
	 * Function to declare a route with the HTTP PATCH protocol
	 * */
	public static function patch(string $uri, Closure|array $function, array $options = []): void
	{
		self::executeRoute(strtolower(self::PATCH), $uri, $function, $options);
		self::addRoutes($uri, self::PATCH, $function, $options);
	}

	/**
	 * Function to declare any route with HTTP protocols
	 * */
	public static function any(string $uri, Closure|array $function, array $options = []): void
	{
		self::executeRoute(strtolower(self::ANY), $uri, $function, $options);
		self::addRoutes($uri, self::ANY, $function, $options);
	}

	/**
	 * Function to declare any route with HTTP protocols or to define the
	 * route with certain HTTP protocols
	 * */
	public static function match(array $methods, string $uri, Closure|array $function, array $options = []): void
	{
		foreach ($methods as $method) {
			self::executeRoute(strtolower(trim($method)), $uri, $function, $options);
			self::addRoutes($uri, strtoupper(trim($method)), $function, $options);
		}
	}

	/**
	 * Defines the group to group the defined routes
	 * */
	public static function prefix(string $name, Closure $closure): void
	{
		$previousPrefix = self::$prefix;
		self::$prefix .= "{$name}/";
		self::$router->group([self::PREFIX => $name], $closure);
		self::$prefix = $previousPrefix;
	}

	/**
	 * Defines filters to group defined routes
	 * */
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
