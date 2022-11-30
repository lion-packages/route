<?php

namespace LionRoute\Interface;

use \Closure;

interface iHttp {

	public static function get(string $uri, Closure|array|string $function, array $options = []): void;
	public static function post(string $uri, Closure|array|string $function, array $options = []): void;
	public static function put(string $uri, Closure|array|string $function, array $options = []): void;
	public static function delete(string $uri, Closure|array|string $function, array $options = []): void;
	public static function any(string $uri, Closure|array|string $function, array $options = []): void;
	public static function head(string $uri, Closure|array|string $function, array $options = []): void;
	public static function options(string $uri, Closure|array|string $function, array $options = []): void;
	public static function patch(string $uri, Closure|array|string $function, array $options = []): void;

}