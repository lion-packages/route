<?php

namespace LionRoute\Config;

class RouteConfig {

	public function __construct() {

	}

	public static function processInput(int $index): string {
		return implode('/', array_slice(explode('/', $_SERVER['REQUEST_URI']), $index));
	}

	public static function processOutput($response): void {
		echo(json_encode($response));
		exit();
	}

}