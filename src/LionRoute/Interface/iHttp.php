<?php

namespace LionRoute\Interface;

use \Closure;

interface iHttp {

	public static function get(string $url, Closure|array|string $function, array $filters = []): void;
	public static function post(string $url, Closure|array|string $function, array $filters = []): void;
	public static function put(string $url, Closure|array|string $function, array $filters = []): void;
	public static function delete(string $url, Closure|array|string $function, array $filters = []): void;
	public static function any(string $url, Closure|array|string $function, array $filters = []): void;
	public static function head(string $url, Closure|array|string $function, array $filters = []): void;
	public static function options(string $url, Closure|array|string $function, array $filters = []): void;
	public static function patch(string $url, Closure|array|string $function, array $filters = []): void;

}