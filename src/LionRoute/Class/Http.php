<?php

namespace LionRoute\Class;

use \Closure;
use GuzzleHttp\Client;
use Phroute\Phroute\RouteCollector;
use LionRoute\Interface\iHttp;

class Http implements iHttp {

	protected static ?Client $client;
	protected static RouteCollector $router;

	protected static array $values = [];
	protected static array $routes = [];
	protected static array $filters = [];
	protected static string $prefix = "";

	private static function extractParameters() {
		$urls = array_filter(array_keys(self::$routes), fn($url) => preg_match('/\{.*\}/', $url));
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

	private static function executeRoute(string $type, string $uri, Closure|array|string $function, array $options): void {
		if (count($options) > 0) {
			self::$router->$type($uri, $function, isset($options[1])
				? ['before' => $options[0], 'after' => $options[1]]
				: ['before' => $options[0]]
			);
		} else {
			self::$router->$type($uri, $function);
		}
	}

	private static function executeRequest(string $type, string $uri, string $function, array $options): void {
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

	private static function addRoutes(string $uri, $method): void {
		$new_uri = str_replace("//", "/", (self::$prefix . $uri));

		if (!isset(self::$routes[$new_uri][$method])) {
			self::$routes[$new_uri][$method] = [
				'filters' => self::$filters
			];
		} else {
			self::$routes[$new_uri][$method]['filters'] = [
				...self::$routes[$new_uri][$method]['filters'],
				...self::$filters
			];
		}

        self::extractParameters();
	}

	public static function get(string $uri, Closure|array|string $function, array $options = []): void {
		self::addRoutes($uri, "GET");

		if (gettype($function) === 'object' || gettype($function) === 'array') {
			self::executeRoute('get', $uri, $function, $options);
		} elseif (gettype($function) === 'string') {
			self::executeRequest('get', $uri, $function, $options);
		}
	}

	public static function post(string $uri, Closure|array|string $function, array $options = []): void {
		self::addRoutes($uri, "POST");

		if (gettype($function) === 'object' || gettype($function) === 'array') {
			self::executeRoute('post', $uri, $function, $options);
		} elseif (gettype($function) === 'string') {
			self::executeRequest('post', $uri, $function, $options);
		}
	}

	public static function put(string $uri, Closure|array|string $function, array $options = []): void {
		self::addRoutes($uri, "PUT");

		if (gettype($function) === 'object' || gettype($function) === 'array') {
			self::executeRoute('put', $uri, $function, $options);
		} elseif (gettype($function) === 'string') {
			self::executeRequest('put', $uri, $function, $options);
		}
	}

	public static function delete(string $uri, Closure|array|string $function, array $options = []): void {
		self::addRoutes($uri, "DELETE");

		if (gettype($function) === 'object' || gettype($function) === 'array') {
			self::executeRoute('delete', $uri, $function, $options);
		} elseif (gettype($function) === 'string') {
			self::executeRequest('delete', $uri, $function, $options);
		}
	}

	public static function any(string $uri, Closure|array|string $function, array $options = []): void {
		self::addRoutes($uri, "ANY");

		if (gettype($function) === 'object' || gettype($function) === 'array') {
			self::executeRoute('any', $uri, $function, $options);
		} elseif (gettype($function) === 'string') {
			self::executeRequest('any', $uri, $function, $options);
		}
	}

	public static function head(string $uri, Closure|array|string $function, array $options = []): void {
		self::addRoutes($uri, "HEAD");

		if (gettype($function) === 'object' || gettype($function) === 'array') {
			self::executeRoute('head', $uri, $function, $options);
		} elseif (gettype($function) === 'string') {
			self::executeRequest('head', $uri, $function, $options);
		}
	}

	public static function options(string $uri, Closure|array|string $function, array $options = []): void {
		self::addRoutes($uri, "OPTIONS");

		if (gettype($function) === 'object' || gettype($function) === 'array') {
			self::executeRoute('options', $uri, $function, $options);
		} elseif (gettype($function) === 'string') {
			self::executeRequest('options', $uri, $function, $options);
		}
	}

	public static function patch(string $uri, Closure|array|string $function, array $options = []): void {
		self::addRoutes($uri, "PATCH");

		if (gettype($function) === 'object' || gettype($function) === 'array') {
			self::executeRoute('patch', $uri, $function, $options);
		} elseif (gettype($function) === 'string') {
			self::executeRequest('patch', $uri, $function, $options);
		}
	}

}