<?php

namespace LionRoute\Class;

use \Closure;
use GuzzleHttp\Client;
use Phroute\Phroute\RouteCollector;
use LionRoute\Interface\iHttp;

class Http implements iHttp {

	protected static ?Client $client;
	protected static RouteCollector $router;

	protected static array $routes = [];
	protected static array $filters = [];
	protected static array $params = [];
	protected static string $prefix = "";

	protected static function executeRoute(string $type, string $uri, Closure|array|string $function, array $options): void {
		if (count($options) > 0) {
			self::$router->$type($uri, $function, isset($options[1])
				? ['before' => $options[0], 'after' => $options[1]]
				: ['before' => $options[0]]
			);
		} else {
			self::$router->$type($uri, $function);
		}
	}

	protected static function executeRequest(string $type, string $uri, string $function, array $options): void {
		if (isset($options['middleware'])) {
			$count = count($options['middleware']);
			$list_middleware = [];

			if ($count === 1) {
				$list_middleware = [
					'before' => $options['middleware'][0]
				];
			} elseif ($count === 2) {
				$list_middleware = [
					'before' => $options['middleware'][0],
					'after' => $options['middleware'][1]
				];
			} elseif ($count >= 3) {
				$list_middleware = [
					'before' => $options['middleware'][0],
					'after' => $options['middleware'][1],
					'prefix' => $options['middleware'][2]
				];
			}

			self::$router->group($list_middleware, function($router) use ($type, $uri, $function, $options) {
				self::$router->$type($uri, function() use ($type, $function, $options) {
					if (in_array($type, ['delete', 'put', 'patch'])) {
						$str_params = "";
						$size = count($options['uri_params']) - 1;

						foreach ($options['uri_params'] as $key => $param) {
							$str_params .= $key === $size ? "/{$param}" : "/{$param}/";
						}

						$function .= $str_params;
					}

					return json_decode(self::$client->$type($function, $options)->getBody());
				});
			});
		} else {
			self::$router->$type($uri, function() use ($type, $function, $options) {
				if (in_array($type, ['delete', 'put', 'patch'])) {
					$str_params = "";
					$size = count($options['uri_params']) - 1;

					foreach ($options['uri_params'] as $key => $param) {
						$str_params .= $key === $size ? "/{$param}" : "/{$param}/";
					}

					$function .= $str_params;
				}

				return json_decode(self::$client->$type($function, $options)->getBody());
			});
		}
	}

	protected static function addRoutes(string $uri, string $method, Closure|array|string $function, array $options): void {
		$new_uri = str_replace("//", "/", (self::$prefix . $uri));

		if (!isset(self::$routes[$new_uri][$method])) {
			self::$routes[$new_uri][$method] = [
				'filters' => [...self::$filters, ...$options],
				'handler' => [
					'controller' => !is_array($function) ? false : [
						'name' => $function[0],
						'function' => $function[1],
					],
					'callback' => is_array($function) ? false : (is_string($function) ? false : true),
					'request' => !is_string($function) ? false : [
						'url' => $function
					]
				]
			];
		} else {
			self::$routes[$new_uri][$method]['filters'] = [
				...self::$routes[$new_uri][$method]['filters'],
				...self::$filters,
				...$options
			];

			self::$routes[$new_uri][$method]['handler'] = [
				'controller' => !is_array($function) ? false : [
					'name' => $function[0],
					'function' => $function[1],
				],
				'callback' => is_array($function) ? false : (is_string($function) ? false : true),
				'request' => !is_string($function) ? false : [
					'url' => $function
				]
			];
		}
	}

	public static function get(string $uri, Closure|array|string $function, array $options = []): void {
		if (gettype($function) === 'object' || gettype($function) === 'array') {
			self::executeRoute('get', $uri, $function, $options);
			self::addRoutes($uri, "GET", $function, $options);
		} elseif (gettype($function) === 'string') {
			self::executeRequest('get', $uri, $function, $options);
			self::addRoutes($uri, "GET", $function, isset($options['middleware']) ? $options['middleware'] : $options);
		}
	}

	public static function post(string $uri, Closure|array|string $function, array $options = []): void {
		if (gettype($function) === 'object' || gettype($function) === 'array') {
			self::executeRoute('post', $uri, $function, $options);
			self::addRoutes($uri, "POST", $function, $options);
		} elseif (gettype($function) === 'string') {
			self::executeRequest('post', $uri, $function, $options);
			self::addRoutes($uri, "POST", $function, isset($options['middleware']) ? $options['middleware'] : $options);
		}
	}

	public static function put(string $uri, Closure|array|string $function, array $options = []): void {
		if (gettype($function) === 'object' || gettype($function) === 'array') {
			self::executeRoute('put', $uri, $function, $options);
			self::addRoutes($uri, "PUT", $function, $options);
		} elseif (gettype($function) === 'string') {
			self::executeRequest('put', $uri, $function, $options);
			self::addRoutes($uri, "PUT", $function, isset($options['middleware']) ? $options['middleware'] : $options);
		}
	}

	public static function delete(string $uri, Closure|array|string $function, array $options = []): void {
		if (gettype($function) === 'object' || gettype($function) === 'array') {
			self::executeRoute('delete', $uri, $function, $options);
			self::addRoutes($uri, "DELETE", $function, $options);
		} elseif (gettype($function) === 'string') {
			self::executeRequest('delete', $uri, $function, $options);
			self::addRoutes($uri, "DELETE", $function, isset($options['middleware']) ? $options['middleware'] : $options);
		}
	}

	public static function any(string $uri, Closure|array|string $function, array $options = []): void {
		if (gettype($function) === 'object' || gettype($function) === 'array') {
			self::executeRoute('any', $uri, $function, $options);
			self::addRoutes($uri, "ANY", $function, $options);
		} elseif (gettype($function) === 'string') {
			self::executeRequest('any', $uri, $function, $options);
			self::addRoutes($uri, "ANY", $function, isset($options['middleware']) ? $options['middleware'] : $options);
		}
	}

	public static function head(string $uri, Closure|array|string $function, array $options = []): void {
		if (gettype($function) === 'object' || gettype($function) === 'array') {
			self::executeRoute('head', $uri, $function, $options);
			self::addRoutes($uri, "HEAD", $function, $options);
		} elseif (gettype($function) === 'string') {
			self::executeRequest('head', $uri, $function, $options);
			self::addRoutes($uri, "HEAD", $function, isset($options['middleware']) ? $options['middleware'] : $options);
		}
	}

	public static function options(string $uri, Closure|array|string $function, array $options = []): void {
		if (gettype($function) === 'object' || gettype($function) === 'array') {
			self::executeRoute('options', $uri, $function, $options);
			self::addRoutes($uri, "OPTIONS", $function, $options);
		} elseif (gettype($function) === 'string') {
			self::executeRequest('options', $uri, $function, $options);
			self::addRoutes($uri, "OPTIONS", $function, isset($options['middleware']) ? $options['middleware'] : $options);
		}
	}

	public static function patch(string $uri, Closure|array|string $function, array $options = []): void {
		if (gettype($function) === 'object' || gettype($function) === 'array') {
			self::executeRoute('patch', $uri, $function, $options);
			self::addRoutes($uri, "PATCH", $function, $options);
		} elseif (gettype($function) === 'string') {
			self::executeRequest('patch', $uri, $function, $options);
			self::addRoutes($uri, "PATCH", $function, isset($options['middleware']) ? $options['middleware'] : $options);
		}
	}

}