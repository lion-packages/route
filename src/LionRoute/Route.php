<?php

namespace LionRoute;

use Phroute\Phroute\{ RouteCollector, RouteParser, Dispatcher };
use Phroute\Phroute\Exception\{ HttpRouteNotFoundException, HttpMethodNotAllowedException };
use LionRoute\Middleware;

class Route {

	private static RouteCollector $router;
	private static array $addMiddleware = [];
	
	public function __construct() {
		
	}

	public static function init(): Route {
		self::$router = new RouteCollector(new RouteParser());
		return new Route();
	}

	public static function getRoutes(): array {
		return (array) self::$router;
	}

	public static function newMiddleware(array $middleware): void {
		if (count($middleware) > 0) {
			foreach ($middleware as $key => $add) {
				array_push(self::$addMiddleware, new Middleware($add[0], $add[1], $add[2]));
			}
		}

		self::createMiddleware();
	}

	private static function createMiddleware(): void {
		if (count(self::$addMiddleware) > 0) {
			foreach (self::$addMiddleware as $key => $obj) {
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
		self::executeMethod('get', $url, $controller_function, $filters);
	}

	public static function post(string $url, \Closure|array $controller_function, array $filters = []): void {
		self::executeMethod('post', $url, $controller_function, $filters);
	}

	public static function put(string $url, \Closure|array $controller_function, array $filters = []): void {
		self::executeMethod('put', $url, $controller_function, $filters);
	}

	public static function delete(string $url, \Closure|array $controller_function, array $filters = []): void {
		self::executeMethod('delete', $url, $controller_function, $filters);
	}

	public static function any(string $url, \Closure|array $controller_function, array $filters = []): void {
		self::executeMethod('any', $url, $controller_function, $filters);
	}

	private static function executeMethod(string $methodType, string $url, \Closure|array $controller_function, array $filters = []): void {
		if (count($filters) > 0) {
			self::$router->$methodType(
				$url,
				$controller_function,
				isset($filters[1]) ? ['before' => $filters[0], 'after' => $filters[1]] : ['before' => $filters[0]]
			);
		} else {
			self::$router->$methodType($url, $controller_function);
		}
	}

	private static function processInput(int $index): string {
		return implode('/', array_slice(explode('/', $_SERVER['REQUEST_URI']), $index));
	}

	private static function processOutput($response): void {
		echo(json_encode($response));
	}

	public static function dispatch(int $index): void {
		try {
			self::processOutput(
				(new Dispatcher(self::$router->getData()))->dispatch(
					$_SERVER['REQUEST_METHOD'],
					self::processInput($index)
				)
			);
		} catch (HttpRouteNotFoundException $e) {
			self::processOutput(
				['status' => "error", 'message' => "Path not found: {$e->getMessage()}"]
			);
		} catch (HttpMethodNotAllowedException $e) {
			self::processOutput(
				['status' => "error", 'message' => "Method not allowed: {$e->getMessage()}"]
			);
		}
	}

}