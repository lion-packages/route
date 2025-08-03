<?php

declare(strict_types=1);

namespace Tests\Provider;

use Lion\Route\Route;

trait HttpMethodsProviderTrait
{
    public const DATA_METHOD = [
        'filters' => [],
        'handler' => [
            'controller' => false,
            'callback' => true,
        ]
    ];
    public const DATA_METHOD_MIDDLEWARE = [
        'filters' => [
            'example-method-1',
            'example-method-2',
        ],
        'handler' => [
            'controller' => false,
            'callback' => true,
        ],
    ];
    public const DATA_METHOD_CONTROLLER = [
        'filters' => [],
        'handler' => [
            'controller' => [
                'name' => ControllerProvider::class,
                'function' => 'getMiddleware',
            ],
            'callback' => false,
        ],
    ];
    public const FILTER_NAME_1 = 'example-method-1';
    public const FILTER_NAME_2 = 'example-method-2';
    public const FILTER_NAME_3 = 'example-method-3';
    public const FILTERS_MIDDLEWARE = [self::FILTER_NAME_1, self::FILTER_NAME_2, self::FILTER_NAME_3];
    public const ROUTES = [
        Route::GET => self::DATA_METHOD,
        Route::POST => self::DATA_METHOD,
        Route::PUT => self::DATA_METHOD,
        Route::DELETE => self::DATA_METHOD,
        Route::HEAD => self::DATA_METHOD,
        Route::OPTIONS => self::DATA_METHOD,
        Route::PATCH => self::DATA_METHOD,
        Route::ANY => self::DATA_METHOD
    ];
    public const ROUTES_CONTROLLER = [
        Route::GET => self::DATA_METHOD_CONTROLLER,
        Route::POST => self::DATA_METHOD_CONTROLLER,
        Route::PUT => self::DATA_METHOD_CONTROLLER,
        Route::DELETE => self::DATA_METHOD_CONTROLLER,
        Route::HEAD => self::DATA_METHOD_CONTROLLER,
        Route::OPTIONS => self::DATA_METHOD_CONTROLLER,
        Route::PATCH => self::DATA_METHOD_CONTROLLER,
        Route::ANY => self::DATA_METHOD_CONTROLLER
    ];

    public static function httpMethodsProvider(): array
    {
        return [
            [
                'method' => 'get',
                'httpMethod' => Route::GET,
            ],
            [
                'method' => 'post',
                'httpMethod' => Route::POST,
            ],
            [
                'method' => 'put',
                'httpMethod' => Route::PUT,
            ],
            [
                'method' => 'delete',
                'httpMethod' => Route::DELETE,
            ],
            [
                'method' => 'head',
                'httpMethod' => Route::HEAD,
            ],
            [
                'method' => 'options',
                'httpMethod' => Route::OPTIONS,
            ],
            [
                'method' => 'patch',
                'httpMethod' => Route::PATCH,
            ],
            [
                'method' => 'any',
                'httpMethod' => Route::ANY,
            ]
        ];
    }
}
