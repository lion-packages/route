<?php

declare(strict_types=1);

namespace Tests\Provider;

use Lion\Route\Route;

trait HttpMethodsProviderTrait
{
    private const array DATA_METHOD = [
        'filters' => [],
        'handler' => [
            'controller' => false,
            'callback'   => true,
        ],
    ];
    private const array DATA_METHOD_MIDDLEWARE = [
        'filters' => [
            'example-method-1',
            'example-method-2',
        ],
        'handler' => [
            'controller' => false,
            'callback'   => true,
        ],
    ];
    private const string FILTER_NAME_1 = 'example-method-1';
    private const string FILTER_NAME_2 = 'example-method-2';
    private const string FILTER_NAME_3 = 'example-method-3';
    private const array ROUTES = [
        Route::GET     => self::DATA_METHOD,
        Route::POST    => self::DATA_METHOD,
        Route::PUT     => self::DATA_METHOD,
        Route::DELETE  => self::DATA_METHOD,
        Route::HEAD    => self::DATA_METHOD,
        Route::OPTIONS => self::DATA_METHOD,
        Route::PATCH   => self::DATA_METHOD,
        Route::ANY     => self::DATA_METHOD,
    ];

    /**
     * @return array<int, array<string, string>>
     */
    public static function httpMethodsProvider(): array
    {
        return [
            [
                'method'     => 'get',
                'httpMethod' => Route::GET,
            ],
            [
                'method'     => 'post',
                'httpMethod' => Route::POST,
            ],
            [
                'method'     => 'put',
                'httpMethod' => Route::PUT,
            ],
            [
                'method'     => 'delete',
                'httpMethod' => Route::DELETE,
            ],
            [
                'method'     => 'head',
                'httpMethod' => Route::HEAD,
            ],
            [
                'method'     => 'options',
                'httpMethod' => Route::OPTIONS,
            ],
            [
                'method'     => 'patch',
                'httpMethod' => Route::PATCH,
            ],
            [
                'method'     => 'any',
                'httpMethod' => Route::ANY,
            ],
        ];
    }
}
