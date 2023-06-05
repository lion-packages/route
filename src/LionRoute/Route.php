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
	private static bool $active_function = false;

	public static function init(int $index = 1): void {
		self::$index = $index;
		self::$router = new RouteCollector();
		$_SERVER['REQUEST_URI'] = explode('?', $_SERVER['REQUEST_URI'])[0];
	}

	// ---------------------------------------------------------------------------------------------

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

	public static function addLog(): void {
		self::$active_function = function_exists("logger") ? true : false;
	}

	private static function extractParameters() {
		$urls = array_filter(self::getFullRoutes(), fn($url) => preg_match('/\{.*\}/', $url));
		$params = [];
		$arrayUrl = explode('/', $_SERVER['REQUEST_URI']);
		$sizeUrl = count($arrayUrl);

		foreach ($urls as $position => $uri) {
			$arrayUri = explode("/", "/{$uri}");
			$sizeUri = count($arrayUri);

			if ($sizeUrl === $sizeUri) {
				$newArrayUri = array_filter($arrayUri, fn($url) => !preg_match('/\{.*\}/', $url) && $url != "");
				$sizeItemUri = 0;

				foreach ($newArrayUri as $key => $itemUri) {
					if ((bool) preg_match("/" . $itemUri . "/i", $_SERVER['REQUEST_URI'])) {
						$sizeItemUri++;
					}
				}

				if ($sizeItemUri === count($newArrayUri)) {
					foreach ($arrayUri as $keyPosition => $value) {
						if ((bool) preg_match('/^\{.*\}$/', $value)) {
							$split = explode(":", str_replace(['{', '}'], '', $value));
							$params[$split[0]] = $arrayUrl[$keyPosition];
						}
					}
				}
			}
		}

		foreach ($params as $key => $param) {
			self::$values[$key] = $param;
		}
	}

	public static function getValues(): array {
		return self::$values;
	}

	public static function getFullRoutes(): array {
		return array_map(fn($url) => str_replace("//", "/", $url), self::$routes);
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
		self::extractParameters();

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