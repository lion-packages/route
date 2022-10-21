<?php

namespace LionRoute;

use \Closure;
use Phroute\Phroute\{ RouteCollector, RouteParser, Dispatcher };
use Phroute\Phroute\Exception\{ HttpRouteNotFoundException, HttpMethodNotAllowedException };
use LionRoute\Config\RouteConfig;
use LionRoute\{ Http, Middleware };
use LionRoute\Traits\Singleton;

class Route extends Http {

	use Singleton;

	protected static array $addMiddleware = [];

	public static function init(int $index = 1): void {
		self::$index = $index;
		self::$router = new RouteCollector();
	}

	public static function getRoutes(): array {
		return self::$router->getData()->getStaticRoutes();
	}

	public static function newMiddleware(array $middleware): void {
		if (count($middleware) > 0) {
			foreach ($middleware as $key => $class) {
				foreach ($class as $key_class => $add) {
					array_push(self::$addMiddleware, new Middleware($add['name'], $key, $add['method']));
				}
			}

			self::createMiddleware();
		}
	}

	private static function createMiddleware(): void {
		foreach (self::$addMiddleware as $key => $obj) {
			self::$router->filter($obj->getMiddlewareName(), function() use ($obj) {
				$objectClass = $obj->getNewObjectClass();
				$methodClass = $obj->getMethodClass();
				$objectClass->$methodClass();
			});
		}
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
				'status' => "route-error",
				'message' => $e->getMessage()
			]);
		} catch (HttpMethodNotAllowedException $e) {
			RouteConfig::processOutput([
				'status' => "route-error",
				'message' => $e->getMessage()
			]);
		}
	}

}