<?php 

require_once(__DIR__ . './../vendor/autoload.php');

header('Content-Type: application/json');

use Lion\Route\Middleware;
use Lion\Route\Route;
use Tests\Provider\ControllerProvider;

$content = json_decode(file_get_contents("php://input"), true);
$_POST = $content === null ? $_POST : $content;

$classExample = new class {
	public function exampleMethod1(): void
	{
		if (!isset($_POST['id'])) {
			die(json_encode(['message' => 'property is required: id', 'isValid' => false]));
		}
	}

	public function exampleMethod2(): void
	{
		if (!isset($_POST['name'])) {
			die(json_encode(['message' => 'property is required: name', 'isValid' => false]));
		}
	}

	public function exampleMethod3(): void
	{
		if (!isset($_POST['last_name'])) {
			die(json_encode(['message' => 'property is required: last_name', 'isValid' => false]));
		}
	}

	public function exampleMethod4(): void
	{
		if (!isset($_POST['email'])) {
			die(json_encode(['message' => 'property is required: email', 'isValid' => false]));
		}
	}

	public function exampleMethod5(): void
	{
		if (!isset($_POST['password'])) {
			die(json_encode(['message' => 'property is required: password', 'isValid' => false]));
		}
	}
};

Route::init();

Route::addMiddleware([
	$classExample::class => [
		['name' => 'example-method-1', 'method' => 'exampleMethod1'],
		['name' => 'example-method-2', 'method' => 'exampleMethod2'],
		['name' => 'example-method-3', 'method' => 'exampleMethod3'],
		['name' => 'example-method-4', 'method' => 'exampleMethod4'],
		['name' => 'example-method-5', 'method' => 'exampleMethod5']
	]
]);

Route::get('/', fn() => ['isValid' => true]);
Route::get('controller', [ControllerProvider::class, 'middleware']);
Route::get('controller/{middleware}', [ControllerProvider::class, 'setMiddleware']);
Route::get('controller/middleware/get', [ControllerProvider::class, 'getMiddleware']);

Route::get('controller-index', function(Middleware $middleware, string $name = 'Daniel') {
    return [
        'name' => $middleware->setMiddlewareName($name)->getMiddlewareName()
    ];
});

Route::middleware(['example-method-1', 'example-method-2'], function() {
	Route::post('example', function() {
		return ['isValid' => true];
	});

	Route::middleware(['example-method-3', 'example-method-4'], function() {
		Route::post('example-2', function() {
			return ['isValid' => true];
		});
	});

	Route::middleware(['example-method-4', 'example-method-5', 'custom-prefix'], function() {
		Route::post('example-3', function() {
			return ['isValid' => true];
		});

		Route::match([
			...[Route::GET, Route::POST, Route::PUT, Route::DELETE],
			...[Route::HEAD, Route::PATCH, Route::OPTIONS]
		], 'example-4', function() {
			return ['isValid' => true];
		});

		Route::any('example-5', function() {
			return ['isValid' => true];
		});
	});
});

Route::dispatch();
