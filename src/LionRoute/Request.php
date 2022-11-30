<?php

namespace LionRoute;

use GuzzleHttp\Client;
use LionRoute\Class\Http;
use LionRoute\Traits\Singleton;

class Request extends Http {

	use Singleton;

	public static function init(Client $client): void {
		self::$client = $client;
	}

}