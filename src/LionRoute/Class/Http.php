<?php

namespace LionRoute\Class;

use \Closure;
use Phroute\Phroute\RouteCollector;
use LionRoute\Interface\iHttp;

class Http implements iHttp {

	protected static RouteCollector $router;

	public static function get(string $url, Closure|array|string $function, array $filters = []): void {
		if (count($filters) > 0) {
			self::$router->get(
				$url,
				$function,
				isset($filters[1]) ? ['before' => $filters[0], 'after' => $filters[1]] : ['before' => $filters[0]]
			);
		} else {
			self::$router->get($url, $function);
		}
	}

	public static function post(string $url, Closure|array|string $function, array $filters = []): void {
		if (count($filters) > 0) {
			self::$router->post(
				$url,
				$function,
				isset($filters[1]) ? ['before' => $filters[0], 'after' => $filters[1]] : ['before' => $filters[0]]
			);
		} else {
			self::$router->post($url, $function);
		}
	}

	public static function put(string $url, Closure|array|string $function, array $filters = []): void {
		if (count($filters) > 0) {
			self::$router->put(
				$url,
				$function,
				isset($filters[1]) ? ['before' => $filters[0], 'after' => $filters[1]] : ['before' => $filters[0]]
			);
		} else {
			self::$router->put($url, $function);
		}
	}

	public static function delete(string $url, Closure|array|string $function, array $filters = []): void {
		if (count($filters) > 0) {
			self::$router->delete(
				$url,
				$function,
				isset($filters[1]) ? ['before' => $filters[0], 'after' => $filters[1]] : ['before' => $filters[0]]
			);
		} else {
			self::$router->delete($url, $function);
		}
	}

	public static function any(string $url, Closure|array|string $function, array $filters = []): void {
		if (count($filters) > 0) {
			self::$router->any(
				$url,
				$function,
				isset($filters[1]) ? ['before' => $filters[0], 'after' => $filters[1]] : ['before' => $filters[0]]
			);
		} else {
			self::$router->any($url, $function);
		}
	}

	public static function head(string $url, Closure|array|string $function, array $filters = []): void {
		if (count($filters) > 0) {
			self::$router->head(
				$url,
				$function,
				isset($filters[1]) ? ['before' => $filters[0], 'after' => $filters[1]] : ['before' => $filters[0]]
			);
		} else {
			self::$router->head($url, $function);
		}
	}

	public static function options(string $url, Closure|array|string $function, array $filters = []): void {
		if (count($filters) > 0) {
			self::$router->options(
				$url,
				$function,
				isset($filters[1]) ? ['before' => $filters[0], 'after' => $filters[1]] : ['before' => $filters[0]]
			);
		} else {
			self::$router->options($url, $function);
		}
	}

	public static function patch(string $url, Closure|array|string $function, array $filters = []): void {
		if (count($filters) > 0) {
			self::$router->patch(
				$url,
				$function,
				isset($filters[1]) ? ['before' => $filters[0], 'after' => $filters[1]] : ['before' => $filters[0]]
			);
		} else {
			self::$router->patch($url, $function);
		}
	}

}