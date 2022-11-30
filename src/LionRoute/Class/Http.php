<?php

namespace LionRoute\Class;

use \Closure;
use GuzzleHttp\Client;
use Phroute\Phroute\RouteCollector;
use LionRoute\Interface\iHttp;

class Http implements iHttp {

	protected static ?Client $client;
	protected static RouteCollector $router;

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

	public static function get(string $uri, Closure|array|string $function, array $options = []): void {
		if (gettype($function) === 'object' || gettype($function) === 'array') {
			self::executeRoute('get', $uri, $function, $options);
		} elseif (gettype($function) === 'string') {
			self::executeRequest('get', $uri, $function, $options);
		}
	}

	public static function post(string $uri, Closure|array|string $function, array $options = []): void {
		if (gettype($function) === 'object' || gettype($function) === 'array') {
			self::executeRoute('post', $uri, $function, $options);
		} elseif (gettype($function) === 'string') {
			self::executeRequest('post', $uri, $function, $options);
		}
	}

	public static function put(string $uri, Closure|array|string $function, array $options = []): void {
		if (gettype($function) === 'object' || gettype($function) === 'array') {
			self::executeRoute('put', $uri, $function, $options);
		} elseif (gettype($function) === 'string') {
			self::executeRequest('put', $uri, $function, $options);
		}
	}

	public static function delete(string $uri, Closure|array|string $function, array $options = []): void {
		if (gettype($function) === 'object' || gettype($function) === 'array') {
			self::executeRoute('delete', $uri, $function, $options);
		} elseif (gettype($function) === 'string') {
			self::executeRequest('delete', $uri, $function, $options);
		}
	}

	public static function any(string $uri, Closure|array|string $function, array $options = []): void {
		if (gettype($function) === 'object' || gettype($function) === 'array') {
			self::executeRoute('any', $uri, $function, $options);
		} elseif (gettype($function) === 'string') {
			self::executeRequest('any', $uri, $function, $options);
		}
	}

	public static function head(string $uri, Closure|array|string $function, array $options = []): void {
		if (gettype($function) === 'object' || gettype($function) === 'array') {
			self::executeRoute('head', $uri, $function, $options);
		} elseif (gettype($function) === 'string') {
			self::executeRequest('head', $uri, $function, $options);
		}
	}

	public static function options(string $uri, Closure|array|string $function, array $options = []): void {
		if (gettype($function) === 'object' || gettype($function) === 'array') {
			self::executeRoute('options', $uri, $function, $options);
		} elseif (gettype($function) === 'string') {
			self::executeRequest('options', $uri, $function, $options);
		}
	}

	public static function patch(string $uri, Closure|array|string $function, array $options = []): void {
		if (gettype($function) === 'object' || gettype($function) === 'array') {
			self::executeRoute('patch', $uri, $function, $options);
		} elseif (gettype($function) === 'string') {
			self::executeRequest('patch', $uri, $function, $options);
		}
	}

}