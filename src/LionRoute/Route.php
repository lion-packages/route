<?php

namespace LionRoute;

use Phroute\Phroute\RouteCollector;
use Phroute\Phroute\RouteParser;
use Phroute\Phroute\Dispatcher;
use Phroute\Phroute\Exception\HttpRouteNotFoundException;
use Phroute\Phroute\Exception\HttpMethodNotAllowedException;

class Route {

	private static RouteCollector $router;
	
	public function __construct() {
		
	}

	public static function getRoute(): RouteCollector {
		return self::$router;
	}

	public static function router(array $filters = []): void {
		self::$router = new RouteCollector(new RouteParser());
		self::createMiddleware($filters);
	}

	public static function createMiddleware(array $filters): void {
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
		self::$router->group($middleware, function($router) use ($closure) {
			$closure();
		});
	}

	public static function any(string $url, \Closure|array $controller_function, array $filters = []): void {
		if (count($filters) > 0) {
			self::$router->any($url, $controller_function, $filters);
		} else {
			self::$router->any($url, $controller_function);
		}
	}

	public static function delete(string $url, \Closure|array $controller_function, array $filters = []): void {
		if (count($filters) > 0) {
			self::$router->delete($url, $controller_function, $filters);
		} else {
			self::$router->delete($url, $controller_function);
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

	public static function get(string $url, \Closure|array $controller_function, array $filters = []): void {
		if (count($filters) > 0) {
			self::$router->get($url, $controller_function, $filters);
		} else {
			self::$router->get($url, $controller_function);
		}
	}

	public static function newMiddleware(string $middlewareName, string $objectClass, string $methodClass): MiddlewareCapsule {
		return new MiddlewareCapsule($middlewareName, $objectClass, $methodClass);
	}

	private static function processInput($uri) {
		return implode('/', array_slice(explode('/', $_SERVER['REQUEST_URI']), 3));
	}

	public static function dispatch() {
		try {
			return (new Dispatcher(self::$router->getData()))->dispatch(
				$_SERVER['REQUEST_METHOD'], 
				self::processInput($_SERVER['REQUEST_URI'])
			);
		} catch (HttpRouteNotFoundException $e) {
			return new Request("error", "Page not found. [Dispatch]");
		} catch (HttpMethodNotAllowedException $e) {
			return new Request("error", "Method not allowed. [Dispatch]");
		}
	}

	public static function fileGetContents(): void {
		$_POST = json_decode(file_get_contents("php://input"), true);
	}

	public static function processOutput($response): void {
		echo(json_encode($response));
		die();
	}

}