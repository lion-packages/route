<?php

namespace LionRoute;

use \Closure;
use LionRoute\Class\Middleware;
use LionRoute\Class\Screen;
use Phroute\Phroute\Dispatcher;
use Phroute\Phroute\Exception\HttpMethodNotAllowedException;
use Phroute\Phroute\Exception\HttpRouteNotFoundException;
use Phroute\Phroute\RouteCollector;

class Route extends \LionRoute\Class\Http {

	use Traits\Singleton;

	protected static array $addMiddleware = [];
	private static int $index;
	private static bool $active_function = false;

	public static function init(int $index = 1): void {
		self::$index = $index;
		self::$router = new RouteCollector();
		$_SERVER['REQUEST_URI'] = explode('?', $_SERVER['REQUEST_URI'])[0];
	}

	// ---------------------------------------------------------------------------------------------

	public static function match(array $methods, string $uri, Closure|array|string $function, array $options = []) {
		foreach ($methods as $key => $method) {
			if (gettype($function) === 'object' || gettype($function) === 'array') {
				self::executeRoute(strtolower($method), $uri, $function, $options);
				self::addRoutes($uri, strtoupper($method), $function, $options);
			} elseif (gettype($function) === 'string') {
				self::executeRequest(strtolower($method), $uri, $function, $options);
				self::addRoutes($uri, strtoupper($method), $function, isset($options['middleware']) ? $options['middleware'] : $options);
			}
		}
	}

	public static function redirect(string $url): void {
		header("Location: {$url}");
	}

	public static function prefix(string $name, Closure $closure): void {
		$previousPrefix = self::$prefix;
		self::$prefix .= "{$name}/";

		self::$router->group(['prefix' => $name], function($router) use ($closure) {
			$closure();
		});

		self::$prefix = $previousPrefix;
	}

	public static function middleware(array $middlewares, Closure $closure): void {
		$previousPrefix = self::$prefix;
		$count = count($middlewares);
		$list_middleware = [];

		if ($count === 1) {
			array_push(self::$filters, ...$middlewares);

			$list_middleware = [
				'before' => $middlewares[0]
			];
		} elseif ($count === 2) {
			array_push(self::$filters, ...$middlewares);

			$list_middleware = [
				'before' => $middlewares[0],
				'after' => $middlewares[1]
			];
		} elseif ($count >= 3) {
			array_push(self::$filters, $middlewares[0]);
			array_push(self::$filters, $middlewares[1]);
			self::$prefix .= "{$middlewares[2]}/";

			$list_middleware = [
				'before' => $middlewares[0],
				'after' => $middlewares[1],
				'prefix' => $middlewares[2]
			];
		}

		self::$router->group($list_middleware, function($router) use ($closure) {
			$closure();
		});

		self::$filters = [];
		self::$prefix = $previousPrefix;
	}

	// ---------------------------------------------------------------------------------------------

	public static function addLog(): void {
		self::$active_function = function_exists("logger") ? true : false;
	}

	private static function getParams(): array {
		return self::$params;
	}

	public static function getFullRoutes(): array {
		return self::$routes;
	}

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

	public static function dispatch(bool $add_log = true): void {
		try {
			$response = (new Dispatcher(self::$router->getData()))->dispatch(
				$_SERVER['REQUEST_METHOD'],
				Screen::capture(self::$index)
			);

			if ($add_log) {
				if (self::$active_function) {
					logger(json_encode($response), 'info');
				}
			}

			Screen::show($response);
		} catch (HttpRouteNotFoundException $e) {
			if (self::$active_function) {
				logger($e->getMessage(), 'error');
			}

			Screen::show(['status' => "route-error", 'message' => $e->getMessage()]);
		} catch (HttpMethodNotAllowedException $e) {
			if (self::$active_function) {
				logger($e->getMessage(), 'error');
			}

			Screen::show(['status' => "route-error", 'message' => $e->getMessage()]);
		}
	}

}