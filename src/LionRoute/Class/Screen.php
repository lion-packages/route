<?php

namespace LionRoute\Class;

class Screen {

	public static function capture(int $index): string {
		return implode('/', array_slice(explode('/', $_SERVER['REQUEST_URI']), $index));
	}

	public static function show($response): void {
		die(json_encode($response));
	}

}