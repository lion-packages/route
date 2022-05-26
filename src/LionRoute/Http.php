<?php

namespace LionRoute;

use \Closure;
use Phroute\Phroute\RouteCollector;
use LionRoute\Config\RouteConfig;

class Http {

	protected static RouteCollector $router;
	protected static int $index;
	protected static string $original_url_match = "";
	protected static string $url_match = "";

	public function __construct() {

	}

	public static function prefix(string $prefix_name, Closure $closure): void {
		self::$original_url_match.= "{$prefix_name}/";
		self::$url_match.= "{$prefix_name}/";

		self::$router->group(['prefix' => $prefix_name], function($router) use ($closure) {
			$closure();
		});
	}

	public static function middleware(array $middleware, Closure $closure): void {
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

	public static function get(string $url, Closure|array $controller_function, array $filters = []): void {
		if (count($filters) > 0) {
			self::$router->get(
				$url,
				$controller_function,
				isset($filters[1]) ? ['before' => $filters[0], 'after' => $filters[1]] : ['before' => $filters[0]]
			);
		} else {
			self::$router->get($url, $controller_function);
		}
	}

	public static function post(string $url, Closure|array $controller_function, array $filters = []): void {
		if (count($filters) > 0) {
			self::$router->post(
				$url,
				$controller_function,
				isset($filters[1]) ? ['before' => $filters[0], 'after' => $filters[1]] : ['before' => $filters[0]]
			);
		} else {
			self::$router->post($url, $controller_function);
		}
	}

	public static function put(string $url, Closure|array $controller_function, array $filters = []): void {
		if (count($filters) > 0) {
			self::$router->put(
				$url,
				$controller_function,
				isset($filters[1]) ? ['before' => $filters[0], 'after' => $filters[1]] : ['before' => $filters[0]]
			);
		} else {
			self::$router->put($url, $controller_function);
		}
	}

	public static function delete(string $url, Closure|array $controller_function, array $filters = []): void {
		if (count($filters) > 0) {
			self::$router->delete(
				$url,
				$controller_function,
				isset($filters[1]) ? ['before' => $filters[0], 'after' => $filters[1]] : ['before' => $filters[0]]
			);
		} else {
			self::$router->delete($url, $controller_function);
		}
	}

	public static function any(string $url, Closure|array $controller_function, array $filters = []): void {
		if (count($filters) > 0) {
			self::$router->any(
				$url,
				$controller_function,
				isset($filters[1]) ? ['before' => $filters[0], 'after' => $filters[1]] : ['before' => $filters[0]]
			);
		} else {
			self::$router->any($url, $controller_function);
		}
	}

	public static function head(string $url, Closure|array $controller_function, array $filters = []): void {
		if (count($filters) > 0) {
			self::$router->head(
				$url,
				$controller_function,
				isset($filters[1]) ? ['before' => $filters[0], 'after' => $filters[1]] : ['before' => $filters[0]]
			);
		} else {
			self::$router->head($url, $controller_function);
		}
	}

	public static function options(string $url, Closure|array $controller_function, array $filters = []): void {
		if (count($filters) > 0) {
			self::$router->options(
				$url,
				$controller_function,
				isset($filters[1]) ? ['before' => $filters[0], 'after' => $filters[1]] : ['before' => $filters[0]]
			);
		} else {
			self::$router->options($url, $controller_function);
		}
	}

	public static function patch(string $url, Closure|array $controller_function, array $filters = []): void {
		if (count($filters) > 0) {
			self::$router->patch(
				$url,
				$controller_function,
				isset($filters[1]) ? ['before' => $filters[0], 'after' => $filters[1]] : ['before' => $filters[0]]
			);
		} else {
			self::$router->patch($url, $controller_function);
		}
	}

	public static function match(array $methods, string $url, \Closure|array $controller_function, array $filters = []): void {
		$path_url = RouteConfig::processInput(self::$index);
		$path_url = $path_url === "" ? "/" : $path_url;
		$match = self::$url_match != "" ? (self::$original_url_match . $url) : (self::$url_match . $url);

		if ($path_url === $match) {
			$union = "";
			$count = count($methods);

			foreach ($methods as $key => $method) {
				$mayus = strtoupper($method);
				$methods[$key] = $mayus;
				$union.= $key === ($count - 1) ? $mayus : "{$mayus}, ";
			}

			if (in_array($_SERVER['REQUEST_METHOD'], $methods)) {
				if ($_SERVER['REQUEST_METHOD'] === 'GET') {
					self::get($url, $controller_function, $filters);
				}

				if ($_SERVER['REQUEST_METHOD'] === 'POST') {
					self::post($url, $controller_function, $filters);
				}

				if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
					self::put($url, $controller_function, $filters);
				}

				if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
					self::delete($url, $controller_function, $filters);
				}

				if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
					self::head($url, $controller_function, $filters);
				}

				if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
					self::options($url, $controller_function, $filters);
				}

				if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
					self::patch($url, $controller_function, $filters);
				}
			} else {
				RouteConfig::processOutput([
					'status' => "error",
					'message' => "Allow: '{$union}' in '{$match}'"
				]);
			}
		}
	}

}