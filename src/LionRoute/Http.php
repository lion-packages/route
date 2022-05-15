<?php

namespace LionRoute;

use Phroute\Phroute\RouteCollector;

class Http {

	protected static RouteCollector $router;

	public function __construct() {

	}

	public static function get(string $url, \Closure|array $controller_function, array $filters = []): void {
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

	public static function post(string $url, \Closure|array $controller_function, array $filters = []): void {
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

	public static function put(string $url, \Closure|array $controller_function, array $filters = []): void {
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

	public static function delete(string $url, \Closure|array $controller_function, array $filters = []): void {
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

	public static function any(string $url, \Closure|array $controller_function, array $filters = []): void {
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

	public static function head(string $url, \Closure|array $controller_function, array $filters = []): void {
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

	public static function options(string $url, \Closure|array $controller_function, array $filters = []): void {
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

	public static function patch(string $url, \Closure|array $controller_function, array $filters = []): void {
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

}