# This library has a quick use of the router with regular expressions based on [mrjgreen's phroute](https://github.com/mrjgreen/phroute).
[![Latest Stable Version](http://poser.pugx.org/lion-framework/lion-route/v)](https://packagist.org/packages/lion-framework/lion-route) [![Total Downloads](http://poser.pugx.org/lion-framework/lion-route/downloads)](https://packagist.org/packages/lion-framework/lion-route) [![Latest Unstable Version](http://poser.pugx.org/lion-framework/lion-route/v/unstable)](https://packagist.org/packages/lion-framework/lion-route) [![License](http://poser.pugx.org/lion-framework/lion-route/license)](https://packagist.org/packages/lion-framework/lion-route) [![PHP Version Require](http://poser.pugx.org/lion-framework/lion-route/require/php)](https://packagist.org/packages/lion-framework/lion-route)

## Install
```
composer require lion-framework/lion-route
```

## Usage
```php
require_once("vendor/autoload.php");

use LionRoute\Route;

Route::init();

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

Route::init();

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
Is identical to filters, we change the name of `filter` to `middleware`.
`Route::newMiddleware('auth', Auth::class, 'auth')` is the basic syntax for adding a middleware to our RouteCollector object. The first parameter is the name of the middleware. The second parameter is the class referenced and the third parameter the name of the function it belongs to. <br>

```php
'middleware' => [
    Route::newMiddleware('auth', Auth::class, 'auth'),
    Route::newMiddleware('no-auth', Auth::class, 'auth')
]
```

When calling `Route::middleware()` keep in mind that the first parameter is an array loaded with data. <br>

The first index is the middleware at position `before`. <br>
The second index is optional and points to `after`. <br>
The third index is optional and indicates a `prefix` to work the middleware in a more dynamic way. <br>

Take into account that if more than 3 parameters are added, these are left over and do not generate internal errors in their operation.
```php
use LionRoute\Route;
use Example\Auth;

Route::init([
    'middleware' => [
        Route::newMiddleware('auth', Auth::class, 'auth'),
        Route::newMiddleware('no-auth', Auth::class, 'auth')
    ]
]);

Route::middleware(['no-auth'], function() {
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

### Example methods:
#### GET
```php
Route::get('/example-url', function() {
    $get = new Example();
    $get->getMethod();
});

// or

Route::get('/example-url', [Example::class, 'getMethod']);
```

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

#### ANY
```php
Route::any('/example-url', function($id) {
    $any = new Example();
    $any->anyMethod();
});

// or

Route::any('/example-url', [Example::class, 'anyMethod']);
```

## Credits
[PHRoute](https://github.com/mrjgreen/phroute)

## License
Copyright © 2022 [MIT License](https://github.com/Sleon4/Lion-Security/blob/main/LICENSE)