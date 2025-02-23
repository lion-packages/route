<?php

define('LION_START', microtime(true));

require_once(__DIR__ . './../vendor/autoload.php');

header('Content-Type: application/json');

use Lion\Exceptions\Serialize;
use Lion\Route\Interface\MiddlewareInterface;
use Lion\Route\Route;
use Tests\Provider\ControllerProvider;

new Serialize()
    ->exceptionHandler();

$content = json_decode(file_get_contents("php://input"), true);

$_POST = $content === null ? $_POST : $content;

$classExample1 = new class implements MiddlewareInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(): void
    {
        if (!isset($_POST['id'])) {
            die(json_encode([
                'message' => 'property is required: id',
                'isValid' => false,
            ]));
        }
    }
};

$classExample2 = new class implements MiddlewareInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(): void
    {
        if (!isset($_POST['name'])) {
            die(json_encode([
                'message' => 'property is required: name',
                'isValid' => false,
            ]));
        }
    }
};

$classExample3 = new class implements MiddlewareInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(): void
    {
        if (!isset($_POST['last_name'])) {
            die(json_encode([
                'message' => 'property is required: last_name',
                'isValid' => false,
            ]));
        }
    }
};

$classExample4 = new class implements MiddlewareInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(): void
    {
        if (!isset($_POST['email'])) {
            die(json_encode([
                'message' => 'property is required: email',
                'isValid' => false,
            ]));
        }
    }
};

$classExample5 = new class implements MiddlewareInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(): void
    {
        if (!isset($_POST['password'])) {
            die(json_encode([
                'message' => 'property is required: password',
                'isValid' => false,
            ]));
        }
    }
};

Route::init();

Route::addMiddleware([
    'example-method-1' => $classExample1::class,
    'example-method-2' => $classExample2::class,
    'example-method-3' => $classExample3::class,
    'example-method-4' => $classExample4::class,
    'example-method-5' => $classExample5::class,
]);

Route::get('test-example', function () {
    return [
        'message' => 'provider'
    ];
}, ['example-method-1']);

Route::get('/', fn () => ['isValid' => true]);
Route::get('controller', [ControllerProvider::class, 'middleware']);
Route::get('controller/{middleware}', [ControllerProvider::class, 'setMiddleware']);
Route::get('controller/middleware/get', [ControllerProvider::class, 'getMiddleware']);
Route::post('rules', [ControllerProvider::class, 'testAttributes']);

Route::get('controller-index', function (string $name = 'Lion'): array {
    return [
        'name' => $name,
    ];
});

Route::middleware(['example-method-1', 'example-method-2'], function (): void {
    Route::middleware(['example-method-4', 'example-method-5', 'prefix' => 'custom-prefix'], function (): void {
        Route::post('example-3', function (): array {
            return ['isValid' => true];
        });

        Route::match(
            [Route::GET, Route::POST, Route::PUT, Route::DELETE, Route::HEAD, Route::PATCH, Route::OPTIONS],
            'example-4',
            function (): array {
                return ['isValid' => true];
            }
        );

        Route::any('example-5', function (): array {
            return ['isValid' => true];
        });
    });

    Route::post('example', function (): array {
        return ['isValid' => true];
    });

    Route::middleware(['example-method-3', 'example-method-4'], function (): void {
        Route::post('example-2', function (): array {
            return [
                'isValid' => true,
                'filters' => Route::getFilters(),
            ];
        });
    });
});

Route::get('get-routes', function (): array {
    return Route::getRoutes();
});

Route::get('get-full-routes', function (): array {
    return Route::getFullRoutes();
});

Route::get('no-content', function (): object {
    http_response_code(204);

    return (object) [
        'code' => 204,
    ];
});

Route::post('simple-middleware', function (): array {
    return [
        'status' => 'success',
    ];
}, ['example-method-1', 'example-method-2', 'example-method-3', 'example-method-4', 'example-method-5']);

Route::controller(ControllerProvider::class, function (): void {
    Route::get('todo', 'middleware');
    Route::get('todo/{id:i}', 'middleware');
    Route::post('todo', 'middleware');
    Route::put('todo/{id:i}', 'middleware');
    Route::delete('todo/{id:i}', 'middleware');
});

Route::controller(ControllerProvider::class, function (): void {
    Route::get('post', 'middleware');
    Route::get('post/{id:i}', 'middleware');
    Route::post('post', 'middleware');
    Route::put('post/{id:i}', 'middleware');
    Route::delete('post/{id:i}', 'middleware');
});

Route::dispatch();
