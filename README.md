# This library has a quick use of the router with regular expressions based on [mrjgreen's phroute](https://github.com/mrjgreen/phroute).

## Install
```
composer require lion-framework/lion-route
```

## Usage
```php
require_once("vendor/autoload.php");

use LionRoute\Route;

Route::init([
    'class' => [
        'RouteCollector' => Phroute\Phroute\RouteCollector::class,
        'Dispatcher' => Phroute\Phroute\Dispatcher::class
    ]
]);

Route::any('/', function() {
    return [
        'status' => "success",
        'message' => "Hello world"
    ];
});

// 1 is for production and 2+ for local environment.
Route::processOutput(Route::dispatch(2)); 
```

### Defining routes:
```php
use LionRoute\Route;

Route::init([
    'class' => [
        'RouteCollector' => Phroute\Phroute\RouteCollector::class,
        'Dispatcher' => Phroute\Phroute\Dispatcher::class
    ]
]);

Route::get($route, $handler);
Route::post($route, $handler);
Route::put($route, $handler);
Route::delete($route, $handler);
Route::any($route, $handler);
```

This method accepts the HTTP method the route must match, the route pattern and a callable handler, which can be a closure, function name or `['ClassName', 'method']`. [more information in...](https://github.com/mrjgreen/phroute#defining-routes)

### Regex Shortcuts:
```
:i => :/d+                # numbers only
:a => :[a-zA-Z0-9]+       # alphanumeric
:c => :[a-zA-Z0-9+_\-\.]+  # alnumnumeric and + _ - . characters 
:h => :[a-fA-F0-9]+       # hex

use in routes:

'/user/{name:i}'
'/user/{name:a}'
```

### ~~Filters~~ Middleware:
is identical to filters, we change the name of `filter` to `middleware`.
`Route::newMiddleware('auth', Auth::class, 'auth')` is the basic syntax for adding a middleware to our RouteCollector object, The first parameter is the name of the middleware, The second parameter is the class to which that is referenced and the third parameter the name of the function to which it belongs.
```php
use LionRoute\Route;
use Example\Auth;

Route::init([
    'class' => [
        'RouteCollector' => Phroute\Phroute\RouteCollector::class,
        'Dispatcher' => Phroute\Phroute\Dispatcher::class
    ],
    'middleware' => [
        Route::newMiddleware('auth', Auth::class, 'auth'),
        Route::newMiddleware('no-auth', Auth::class, 'auth')
    ]
]);

Route::middleware(['before' => 'auth'], function() {
    Route::post('login', function() {
        return [
            'status' => "success",
            'message' => "Hello world."
        ];
    });
});
```

### Prefix Groups:
```php
Route::prefix('authenticate', function() {
    Route::post('login', function() {
        return [
            'status' => "success",
            'message' => "Hello world."
        ];
    });
});
```

```php
Route::middleware(['before' => 'no-auth'], function() {
    Route::prefix('authenticate', function() {
        Route::post('login', function() {
            return [
                'status' => "success",
                'message' => "Hello world."
            ];
        });
    });
});

Route::middleware(['before' => 'auth'], function() {
    Route::prefix('dashboard', function() {
        Route::get('home', function() {
            return [
                'status' => "success",
                'message' => "GET success."
            ];
        });

        Route::post('home', function() {
            return [
                'status' => "success",
                'message' => "POST success."
            ];
        });
    });
});
```

### Example methods:
#### POST
```php
Route::post('/example-url', function() {
    $post = new Example();
    $post->postMethod();
});

// or

Route::post('/example-url', [Example::class, 'postMethod']);
```

#### PUT
```php
Route::put('/example-url/{id}', function($id) {
    $put = new Example();
    $put->putMethod();
});

// or

Route::put('/example-url/{id}', [Example::class, 'putMethod']);
```

#### DELETE
```php
Route::delete('/example-url/{id}', function($id) {
    $delete = new Example();
    $delete->deleteMethod();
});

// or

Route::delete('/example-url/{id}', [Example::class, 'deleteMethod']);
```

## Credits
[PHRoute](https://github.com/mrjgreen/phroute)

## License
Copyright © 2022 [MIT License](https://github.com/Sleon4/Lion-Security/blob/main/LICENSE)