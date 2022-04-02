<?php

namespace LionRoute;

use Phroute\Phroute\{ RouteCollector, RouteParser, Dispatcher };
use Phroute\Phroute\Exception\{ HttpRouteNotFoundException, HttpMethodNotAllowedException };
use LionRoute\Middleware;

class Route {

	private static $router;
	
	public function __construct() {
		
	}

	public static function init(array $filters = []): void {
		self::$router = new RouteCollector(new RouteParser());
		self::createMiddleware(isset($filters['middleware']) ? $filters['middleware'] : []);
	}

	public static function newMiddleware(string $middlewareName, string $objectClass, string $methodClass): Middleware {
		return new Middleware($middlewareName, $objectClass, $methodClass);
	}

	private static function createMiddleware(array $filters): void {
		if (count($filters) > 0) {
			foreach ($filters as $key => $obj) {
				self::$router->filter($obj->getMiddlewareName(), function() use ($obj) {    
					$objectClass = $obj->getNewObjectClass();
					$methodClass = $obj->getMethodClass();

					$objectClass->$methodClass();
				});
			}
		}
	}

	public static function prefix(string $prefix_name, \Closure $closure): void {
		self::$router->group(['prefix' => $prefix_name], function($router) use ($closure) {
			$closure();
		});
	}

	public static function middleware(array $middleware, \Closure $closure): void {
		$count = count($middleware);
		$list_middleware = [];

		if ($count === 1) {
			$list_middleware = ['before' => $middleware[0]];
		} elseif ($count === 2) {
			$list_middleware = ['before' => $middleware[0], 'after' => $middleware[1]];
		} elseif ($count >= 3) {
			$list_middleware = ['before' => $middleware[0], 'after' => $middleware[1], 'prefix' => $middleware[2]];
		}

		self::$router->group($list_middleware, function($router) use ($closure) {
			$closure();
		});
	}

	public static function get(string $url, \Closure|array $controller_function, array $filters = []): void {
		if (count($filters) > 0) {
			self::$router->get($url, $controller_function, $filters);
		} else {
			self::$router->get($url, $controller_function);
		}
	}

	public static function post(string $url, \Closure|array $controller_function, array $filters = []): void {
		if (count($filters) > 0) {
			self::$router->post($url, $controller_function, $filters);
		} else {
			self::$router->post($url, $controller_function);
		}
	}

	public static function put(string $url, \Closure|array $controller_function, array $filters = []): void {
		if (count($filters) > 0) {
			self::$router->put($url, $controller_function, $filters);
		} else {
			self::$router->put($url, $controller_function);
		}
	}

	public static function delete(string $url, \Closure|array $controller_function, array $filters = []): void {
		if (count($filters) > 0) {
			self::$router->delete($url, $controller_function, $filters);
		} else {
			self::$router->delete($url, $controller_function);
		}
	}

	public static function any(string $url, \Closure|array $controller_function, array $filters = []): void {
		if (count($filters) > 0) {
			self::$router->any($url, $controller_function, $filters);
		} else {
			self::$router->any($url, $controller_function);
		}
	}

	private static function processInput($index): string {
		return implode('/', array_slice(explode('/', $_SERVER['REQUEST_URI']), $index));
	}

	public static function processOutput($response): void {
		echo(json_encode($response));
	}

	public static function dispatch($index) {
		try {
			return (new Dispatcher(self::$router->getData()))->dispatch(
				$_SERVER['REQUEST_METHOD'], 
				self::processInput($index)
			);
		} catch (HttpRouteNotFoundException $e) {
			return ['status' => "error", 'message' => "Path not found: {$e->getMessage()}"];
		} catch (HttpMethodNotAllowedException $e) {
			return ['status' => "error", 'message' => "Method not allowed: {$e->getMessage()}"];
		}
	}

}