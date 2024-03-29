<?php 

require_once(__DIR__ . './../vendor/autoload.php');

header('Content-Type: application/json');

use Lion\Route\Middleware;
use Lion\Route\Route;
use Tests\Provider\ControllerProvider;

$content = json_decode(file_get_contents("php://input"), true);
$_POST = $content === null ? $_POST : $content;

$classExample = new class {
	public function exampleMethod1(ControllerProvider $controllerProvider): void
	{
		if (!isset($_POST['id'])) {
			die(json_encode([
                'message' => 'property is required: id',
                'isValid' => false,
                'data' => $controllerProvider->createMethod()
            ]));
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
    new Middleware('example-method-1', $classExample::class, 'exampleMethod1'),
    new Middleware('example-method-2', $classExample::class, 'exampleMethod2'),
    new Middleware('example-method-3', $classExample::class, 'exampleMethod3'),
    new Middleware('example-method-4', $classExample::class, 'exampleMethod4'),
    new Middleware('example-method-5', $classExample::class, 'exampleMethod5')
]);

Route::get('/', fn() => ['isValid' => true]);
Route::get('controller', [ControllerProvider::class, 'middleware']);
Route::get('controller/{middleware}', [ControllerProvider::class, 'setMiddleware']);
Route::get('controller/middleware/get', [ControllerProvider::class, 'getMiddleware']);

Route::get('controller-index', function(Middleware $middleware, string $name = 'Lion') {
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

		Route::match(
            [Route::GET, Route::POST, Route::PUT, Route::DELETE, Route::HEAD, Route::PATCH, Route::OPTIONS],
            'example-4',
            function() {
    			return ['isValid' => true];
    		}
        );

		Route::any('example-5', function() {
			return ['isValid' => true];
		});
	});
});

Route::dispatch();
