<?php

namespace LionRoute;

use Phroute\Phroute\{ RouteCollector, RouteParser, Dispatcher };
use Phroute\Phroute\Exception\{ HttpRouteNotFoundException, HttpMethodNotAllowedException };
use LionRoute\Config\RouteConfig;
use LionRoute\{ Singleton, Http, Middleware };

class Route extends Http {

	use Singleton;

	protected static array $addMiddleware = [];

	public static function init(int $index = 1): Route {
		self::$index = $index;
		self::$router = new RouteCollector();
		return self::getInstance();
	}

	public static function getRoutes(): array {
		return self::$router->getData()->getStaticRoutes();
	}

	public static function newMiddleware(array $middleware): void {
		if (count($middleware) > 0) {
			foreach ($middleware as $key => $add) {
				array_push(self::$addMiddleware, new Middleware($add[0], $add[1], $add[2]));
			}

			self::createMiddleware();
		}
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

	public static function dispatch(): void {
		try {
			RouteConfig::processOutput(
				(new Dispatcher(self::$router->getData()))->dispatch(
					$_SERVER['REQUEST_METHOD'],
					RouteConfig::processInput(self::$index)
				)
			);
		} catch (HttpRouteNotFoundException $e) {
			RouteConfig::processOutput([
				'status' => "error",
				'message' => "Path not found: {$e->getMessage()}"
			]);
		} catch (HttpMethodNotAllowedException $e) {
			RouteConfig::processOutput([
				'status' => "error",
				'message' => "Method not allowed, {$e->getMessage()}"
			]);
		}
	}

}