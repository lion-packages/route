<?php

namespace LionRoute;

use \Closure;
use LionRoute\Class\Http;
use LionRoute\Class\Middleware;
use LionRoute\Class\Screen;
use LionRoute\Traits\Singleton;
use Phroute\Phroute\Dispatcher;
use Phroute\Phroute\Exception\HttpMethodNotAllowedException;
use Phroute\Phroute\Exception\HttpRouteNotFoundException;
use Phroute\Phroute\RouteCollector;

class Route extends Http {

	use Singleton;

	protected static array $addMiddleware = [];
	private static int $index;

	public static function init(int $index = 1): void {
		self::$index = $index;
		self::$router = new RouteCollector();
	}

	// ---------------------------------------------------------------------------------------------

	public static function redirect(string $url): void {
		header("Location: {$url}");
	}

	public static function prefix(string $prefix_name, Closure $closure): void {
		self::$router->group(['prefix' => $prefix_name], function($router) use ($closure) {
			$closure();
		});
	}

	public static function middleware(array $middleware, Closure $closure): void {
		$count = count($middleware);
		$list_middleware = [];

		if ($count === 1) {
			$list_middleware = [
				'before' => $middleware[0]
			];
		} elseif ($count === 2) {
			$list_middleware = [
				'before' => $middleware[0],
				'after' => $middleware[1]
			];
		} elseif ($count >= 3) {
			$list_middleware = [
				'before' => $middleware[0],
				'after' => $middleware[1],
				'prefix' => $middleware[2]
			];
		}

		self::$router->group($list_middleware, function($router) use ($closure) {
			$closure();
		});
	}

	// ---------------------------------------------------------------------------------------------

	public static function getRoutes(): array {
		return self::$router->getData()->getStaticRoutes();
	}

	public static function getFilters(): array {
		return self::$router->getData()->getFilters();
	}

	public static function getVariables(): array {
		return self::$router->getData()->getVariableRoutes();
	}

	public static function addMiddleware(array $middleware): void {
		if (count($middleware) > 0) {
			foreach ($middleware as $key => $class) {
				foreach ($class as $key_class => $item) {
					$obj = new Middleware($item['name'], $key, $item['method']);

					self::$router->filter($obj->getMiddlewareName(), function() use ($obj) {
						$objectClass = $obj->newObject();
						$methodClass = $obj->getMethodClass();
						$objectClass->$methodClass();
					});
				}
			}
		}
	}

	public static function dispatch(): void {
		try {
			Screen::show(
				(new Dispatcher(self::$router->getData()))->dispatch(
					$_SERVER['REQUEST_METHOD'],
					Screen::capture(self::$index)
				)
			);
		} catch (HttpRouteNotFoundException $e) {
			Screen::show([
				'status' => "route-error",
				'message' => $e->getMessage()
			]);
		} catch (HttpMethodNotAllowedException $e) {
			Screen::show([
				'status' => "route-error",
				'message' => $e->getMessage()
			]);
		}
	}

}