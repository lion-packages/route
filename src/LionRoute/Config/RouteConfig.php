<?php

namespace LionRoute\Config;

class RouteConfig {

	public function __construct() {

	}

	public static function processInput(int $index): string {
		if ($index === 1) {
			return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
		}

		return implode('/', array_slice(explode('/', $_SERVER['REQUEST_URI']), $index));
	}

	public static function processOutput($response): void {
		echo(json_encode($response));
		exit();
	}

}