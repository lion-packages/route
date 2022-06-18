# This library has a quick use of the router with regular expressions based on [phroute](https://github.com/mrjgreen/phroute).

[![Latest Stable Version](http://poser.pugx.org/lion-framework/lion-route/v)](https://packagist.org/packages/lion-framework/lion-route) [![Total Downloads](http://poser.pugx.org/lion-framework/lion-route/downloads)](https://packagist.org/packages/lion-framework/lion-route) [![License](http://poser.pugx.org/lion-framework/lion-route/license)](https://packagist.org/packages/lion-framework/lion-route) [![PHP Version Require](http://poser.pugx.org/lion-framework/lion-route/require/php)](https://packagist.org/packages/lion-framework/lion-route)

## Install
```
composer require lion-framework/lion-route
```

## HTACCESS
```apacheconf
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

## Usage
Start your development server
```shell
php -S localhost:40400
```

It is recommended to start the development server yourself, since software such as `XAMPP, WampServer, BitNami WAMP Stack, Apache Lounge... etc`, provide directories in which to load your PHP projects, This results in running on the browser routes as `'localhost/MyProject/example'`.
This generates a conflict since the route obtained comes by default as `'MyProject/example'`, something completely wrong. You can solve it by indicating from which parameter the URL can be obtained from the `Route::init()` method.

Indicate with an integer from which position the URL will be obtained, By default it is initialized to 1.
```php
/*
    myweb.com/auth/signin/example
    1 -> auth/signin/example
    2 -> signin/example
    3 -> example
    4+ ...
*/
Route::init(1);
```

### DEFINING ROUTES
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

Route::dispatch();
```

### DEFINITION OF ROUTE TYPES
```php
use LionRoute\Route;

Route::init();

Route::get($route, $handler);
Route::post($route, $handler);
Route::put($route, $handler);
Route::delete($route, $handler);
Route::head($route, $handler);
Route::options($route, $handler);
Route::patch($route, $handler);

Route::any($route, $handler);
Route::match($methods, $route, $handler); // beta
```

This method accepts the HTTP method the route must match, the route pattern and a callable handler, which can be a closure, function name or `['ClassName', 'method']`. [more information in...](https://github.com/mrjgreen/phroute#defining-routes)

### REGEX SHORTCUTS
```
:i => :/d+                # numbers only
:a => :[a-zA-Z0-9]+       # alphanumeric
:c => :[a-zA-Z0-9+_\-\.]+  # alnumnumeric and + _ - . characters
:h => :[a-fA-F0-9]+       # hex

use in routes:

'/user/{name:i}'
'/user/{name:a}'
```

### EXAMPLE METHODS
#### GET
```php
use App\Http\Controllers\Home\Example;

Route::get('example-url', function() {
    $get = new Example();
    $get->getMethod();
});

// or

Route::get('example-url', [Example::class, 'getMethod']);
```

#### POST
```php
use App\Http\Controllers\Home\Example;

Route::post('example-url', function() {
    $post = new Example();
    $post->postMethod();
});

// or

Route::post('example-url', [Example::class, 'postMethod']);
```

#### PUT
```php
use App\Http\Controllers\Home\Example;

Route::put('example-url/{id}', function($id) {
    $put = new Example();
    $put->putMethod();
});

// or

Route::put('example-url/{id}', [Example::class, 'putMethod']);
```

#### DELETE
```php
use App\Http\Controllers\Home\Example;

Route::delete('example-url/{id}', function($id) {
    $delete = new Example();
    $delete->deleteMethod();
});

// or

Route::delete('example-url/{id}', [Example::class, 'deleteMethod']);
```

#### ANY
```php
use App\Http\Controllers\Home\Example;

Route::any('example-url', function($id) {
    $any = new Example();
    $any->anyMethod();
});

// or

Route::any('example-url', [Example::class, 'anyMethod']);
```

#### MATCH
Important note: the `match` method is in beta, currently the `match` method works as long as there is no more than one `prefix` created next to it.
```php
use App\Http\Controllers\Home\Example;

Route::match(['POST', 'PUT'], 'example-url', function() {
    $obj = new Example();
    $obj->method();
});

// or

Route::match(['POST', 'PUT'], 'example-url', [Example::class, 'method']);
```

valid example
```php
Route::prefix('reports', function() {
    Route::match(['GET', 'POST'], 'excel', function() {
        // ...
    });

    Route::prefix('admin', function() {
        Route::match(['GET'], 'pdf', function() {
            // ...
        });
    });
});

Route::prefix('customers', function() {
    Route::match(['GET'], 'word', function() {
        // ...
    });
});
```

invalid example
```php
Route::prefix('reports', function() {
    Route::match(['GET', 'POST'], 'excel', function() {
        // ...
    });

    Route::prefix('admin', function() {
        Route::match(['GET'], 'pdf', function() {
            // ...
        });
    });

    Route::prefix('customers', function() {
        Route::match(['GET'], 'word', function() {
            // ...
        });
    });
});
```

### ~~FILTERS~~ MIDDLEWARE
It's identical to filters, we renamed `filter` to `middleware`. `['auth', Auth::class, 'auth']` is the basic syntax for adding a middleware to our RouteCollector object. Each middleware must be encapsulated in an array, where each middleware carries its information within another array. The first parameter is the name of the middleware. The second parameter is the class being referenced and the third parameter the name of the function it belongs to. <br>

```php
use LionRoute\Route;
use App\Http\Middleware\Auth;

Route::init();

Route::newMiddleware([
    ['auth', Auth::class, 'auth'],
    ['no-auth', Auth::class, 'noAuth']
]);
```

```php
// Auth Class

namespace App\Http\Middleware;

class Auth {

    public function __construct() {

    }

    public function auth(): void {
        if (!isset($_SESSION['user_session'])) {
            echo(json_encode([
                'status' => "error",
                'message' => "user session does not exist"
            ]));

            exit(); // exit to end the execution of the process up to that point.
        }
    }

    public function noAuth(): void {
        if (isset($_SESSION['user_session'])) {
            echo(json_encode([
                'status' => "error",
                'message' => "user session exists"
            ]));

            exit(); // exit to end the execution of the process up to that point.
        }
    }

}
```

When calling `Route::middleware()` keep in mind that the first parameter is an array loaded with data. <br>

The first index is the middleware at position `before`. <br>
The second index is optional and points to `after`. <br>
The third index is optional and indicates a `prefix` to work the middleware in a more dynamic way. <br>

Take into account that if more than 3 parameters are added, these are left over and do not generate internal errors in their operation.
```php
use App\Http\Controllers\Home\Example;

Route::middleware(['no-auth'], function() {
    Route::post('login', function() {
        return [
            'status' => "success",
            'message' => "Hello world."
        ];
    });
});

// or

Route::middleware(['no-auth'], function() {
    Route::post('login', [Example::class, 'postMethod']);
});

// or

Route::post('login', function() {
    return [
        'status' => "success",
        'message' => "Hello world."
    ];
}, ['no-auth']);

// or

Route::post('login', [Example::class, 'postMethod'], ['no-auth']);
```

### PREFIX GROUPS
```php
Route::prefix('authenticate', function() {
    Route::post('login', function() {
        return [
            'status' => "success",
            'message' => "Hello world."
        ];
    });
});

Route::prefix('reports', function() {
    Route::middleware(['auth'], function() {
        Route::post('excel', [Example::class, 'excelMethod']);
        Route::post('word', [Example::class, 'wordMethod']);
        Route::post('power-point', [Example::class, 'powerPointMethod']);
    });

    Route::post('pdf', [Example::class, 'pdfMethod']);
});
```

## Credits
[PHRoute](https://github.com/mrjgreen/phroute)

## License
Copyright © 2022 [MIT License](https://github.com/Sleon4/Lion-Security/blob/main/LICENSE)