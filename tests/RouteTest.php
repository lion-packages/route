<?php

declare(strict_types=1);

namespace Tests;

use GuzzleHttp\Client;
use Lion\Route\Middleware;
use Lion\Route\Route;
use Lion\Test\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Provider\HttpMethodsProviderTrait;

class RouteTest extends Test
{
    use HttpMethodsProviderTrait;

    const string HOST = 'http://127.0.0.1:8000';
    const string API_CONTROLLER = self::HOST . '/controller/';
    const string API_TEST = self::HOST . '/example';
    const string PREFIX = 'prefix-test';
    const string URI = 'test';
    const string FULL_URI = self::PREFIX . '/' . self::URI;
    const string FULL_URI_SECOND = self::PREFIX . '/' . self::PREFIX . '/' . self::URI;
    const string URI_MATCH = 'match-test';
    const array ARRAY_RESPONSE = [
        'isValid' => true,
    ];
    const array JSON_RESPONSE = [
        'message' => 'property is required: id',
        'isValid' => false,
        'data' => [
            'status' => 'success',
            'message' => 'controller provider',
        ],
    ];

    private Route $route;
    private Client $client;
    private object $customClass;

    protected function setUp(): void
    {
        $this->route = new Route();

        $this->client = new Client();

        $this->customClass = new class
        {
            public function exampleMethod1(): void
            {
                echo ('TESTING');
            }

            public function exampleMethod2(): void
            {
                echo ('TESTING');
            }

            public function exampleMethod3(int $key): void
            {
                echo ('TESTING: ' . $key);
            }
        };

        $this->route->init();

        $this->initReflection($this->route);
    }

    protected function tearDown(): void
    {
        $this->setPrivateProperty('routes', []);

        $this->setPrivateProperty('filters', []);

        $this->setPrivateProperty('prefix', '');
    }

    public function testGetFullRoutes(): void
    {
        $this->assertIsArray($this->route->getFullRoutes());
    }

    public function testGetRoutes(): void
    {
        $this->assertIsArray($this->route->getRoutes());
    }

    public function testGetFilters(): void
    {
        $this->route->addMiddleware([
            new Middleware(self::FILTER_NAME_1, $this->customClass::class, 'exampleMethod1'),
            new Middleware(self::FILTER_NAME_2, $this->customClass::class, 'exampleMethod2')
        ]);

        $filters = $this->route->getFilters();

        $this->assertIsArray($filters);
        $this->assertArrayHasKey(self::FILTER_NAME_1, $filters);
        $this->assertArrayHasKey(self::FILTER_NAME_2, $filters);
    }

    public function testGetVariables(): void
    {
        $this->assertIsArray($this->route->getVariables());
    }

    public function testAddMiddleware(): void
    {
        $this->route->addMiddleware([
            new Middleware(self::FILTER_NAME_1, $this->customClass::class, 'exampleMethod1'),
            new Middleware(self::FILTER_NAME_2, $this->customClass::class, 'exampleMethod2'),
            new Middleware(self::FILTER_NAME_3, $this->customClass::class, 'exampleMethod3', ['key2' => 1]),
        ]);

        $this->route->get('test-add-middleware', fn () => self::ARRAY_RESPONSE, [self::FILTER_NAME_1]);

        $filters = $this->route->getFilters();

        $this->assertIsArray($filters);
        $this->assertArrayHasKey(self::FILTER_NAME_1, $filters);
        $this->assertArrayHasKey(self::FILTER_NAME_2, $filters);
    }

    #[DataProvider('httpMethodsProvider')]
    public function testHttpMethods(string $method, string $httpMethod): void
    {
        $this->route->$method(self::URI, fn () => self::ARRAY_RESPONSE);

        $fullRoutes = $this->route->getFullRoutes();

        $this->assertIsArray($fullRoutes);
        $this->assertArrayHasKey(self::URI, $fullRoutes);
        $this->assertArrayHasKey($httpMethod, $fullRoutes[self::URI]);
        $this->assertSame(self::ROUTES[$httpMethod], $fullRoutes[self::URI][$httpMethod]);
    }

    public function testDependencyInjection(): void
    {
        $response = json_decode(
            $this->client
                ->get(self::API_CONTROLLER . self::URI)
                ->getBody()
                ->getContents(),
            true
        );

        $this->assertIsArray($response);
        $this->assertArrayHasKey('middleware', $response);
        $this->assertSame(self::URI, $response['middleware']);
    }

    #[DataProvider('httpMethodsProvider')]
    public function testHttpMethodsWithPrefix(string $method, string $httpMethod): void
    {
        $this->route->prefix(self::PREFIX, function () use ($method) {
            $this->route->$method(self::URI, fn () => self::ARRAY_RESPONSE);
        });

        $fullRoutes = $this->route->getFullRoutes();

        $this->assertArrayHasKey(self::FULL_URI, $fullRoutes);
        $this->assertArrayHasKey($httpMethod, $fullRoutes[self::FULL_URI]);
        $this->assertSame(self::ROUTES[$httpMethod], $fullRoutes[self::FULL_URI][$httpMethod]);
    }

    #[DataProvider('httpMethodsProvider')]
    public function testHttpMethodsWithMiddleware(string $method, string $httpMethod): void
    {
        $this->route->middleware([self::FILTER_NAME_1, self::FILTER_NAME_2], function () use ($method) {
            $this->route->$method(self::URI, fn () => self::ARRAY_RESPONSE);
        });

        $fullRoutes = $this->route->getFullRoutes();

        $this->assertArrayHasKey(self::URI, $fullRoutes);
        $this->assertArrayHasKey($httpMethod, $fullRoutes[self::URI]);
        $this->assertSame(self::DATA_METHOD_MIDDLEWARE, $fullRoutes[self::URI][$httpMethod]);
    }

    public function testMatch(): void
    {
        $this->route->match([Route::GET, Route::POST], self::URI_MATCH, fn () => self::ARRAY_RESPONSE);

        $fullRoutes = $this->route->getFullRoutes();

        $this->assertArrayHasKey(self::URI_MATCH, $fullRoutes);
        $this->assertArrayHasKey(Route::GET, $fullRoutes[self::URI_MATCH]);
        $this->assertSame(self::ROUTES[Route::GET], $fullRoutes[self::URI_MATCH][Route::GET]);
        $this->assertArrayHasKey(Route::POST, $fullRoutes[self::URI_MATCH]);
        $this->assertSame(self::ROUTES[Route::POST], $fullRoutes[self::URI_MATCH][Route::POST]);
    }

    public function testMultipleMatch(): void
    {
        $this->route->match([Route::GET, Route::POST], self::URI, fn () => self::ARRAY_RESPONSE);
        $this->route->match([Route::GET, Route::POST, Route::PUT], self::URI_MATCH, fn () => self::ARRAY_RESPONSE);

        $fullRoutes = $this->route->getFullRoutes();

        $this->assertArrayHasKey(self::URI, $fullRoutes);
        $this->assertArrayHasKey(Route::GET, $fullRoutes[self::URI]);
        $this->assertSame(self::ROUTES[Route::GET], $fullRoutes[self::URI][Route::GET]);
        $this->assertArrayHasKey(Route::POST, $fullRoutes[self::URI]);
        $this->assertSame(self::ROUTES[Route::POST], $fullRoutes[self::URI][Route::POST]);
        $this->assertArrayHasKey(self::URI_MATCH, $fullRoutes);
        $this->assertArrayHasKey(Route::GET, $fullRoutes[self::URI_MATCH]);
        $this->assertSame(self::ROUTES[Route::GET], $fullRoutes[self::URI_MATCH][Route::GET]);
        $this->assertArrayHasKey(Route::POST, $fullRoutes[self::URI_MATCH]);
        $this->assertSame(self::ROUTES[Route::POST], $fullRoutes[self::URI_MATCH][Route::POST]);
    }

    public function testMatchWithPrefix(): void
    {
        $this->route->prefix(self::PREFIX, function () {
            $this->route->match([Route::GET, Route::POST], self::URI, fn () => self::ARRAY_RESPONSE);
        });

        $fullRoutes = $this->route->getFullRoutes();

        $this->assertArrayHasKey(self::FULL_URI, $fullRoutes);
        $this->assertArrayHasKey(Route::GET, $fullRoutes[self::FULL_URI]);
        $this->assertSame(self::ROUTES[Route::GET], $fullRoutes[self::FULL_URI][Route::GET]);
        $this->assertArrayHasKey(Route::POST, $fullRoutes[self::FULL_URI]);
        $this->assertSame(self::ROUTES[Route::POST], $fullRoutes[self::FULL_URI][Route::POST]);
    }

    public function testMatchWithMiddleware(): void
    {
        $this->route->addMiddleware([
            new Middleware(self::FILTER_NAME_1, $this->customClass::class, 'exampleMethod1'),
            new Middleware(self::FILTER_NAME_2, $this->customClass::class, 'exampleMethod2')
        ]);

        $this->route->middleware(
            [self::FILTER_NAME_1, self::FILTER_NAME_2, 'prefix' => self::PREFIX],
            function (): void {
                $this->route->match([Route::GET, Route::POST], self::URI, fn () => self::ARRAY_RESPONSE);
            }
        );

        $fullRoutes = $this->route->getFullRoutes();

        $this->assertArrayHasKey(self::FULL_URI, $fullRoutes);
        $this->assertArrayHasKey(Route::GET, $fullRoutes[self::FULL_URI]);
        $this->assertSame(self::DATA_METHOD_MIDDLEWARE, $fullRoutes[self::FULL_URI][Route::GET]);
        $this->assertArrayHasKey(Route::POST, $fullRoutes[self::FULL_URI]);
        $this->assertSame(self::DATA_METHOD_MIDDLEWARE, $fullRoutes[self::FULL_URI][Route::POST]);
    }

    public function testPrefix(): void
    {
        $this->route->prefix(self::PREFIX, function () {
            $this->route->get(self::URI, fn () => self::ARRAY_RESPONSE);
        });

        $fullRoutes = $this->route->getFullRoutes();

        $this->assertArrayHasKey(self::FULL_URI, $fullRoutes);
        $this->assertArrayHasKey(Route::GET, $fullRoutes[self::FULL_URI]);
        $this->assertSame(self::ROUTES[Route::GET], $fullRoutes[self::FULL_URI][Route::GET]);
    }

    public function testMultiplePrefix(): void
    {
        $this->route->prefix(self::PREFIX, function () {
            $this->route->prefix(self::PREFIX, function () {
                $this->route->get(self::URI, fn () => self::ARRAY_RESPONSE);
            });
        });

        $fullRoutes = $this->route->getFullRoutes();

        $this->assertArrayHasKey(self::FULL_URI_SECOND, $fullRoutes);
        $this->assertArrayHasKey(Route::GET, $fullRoutes[self::FULL_URI_SECOND]);
        $this->assertSame(self::ROUTES[Route::GET], $fullRoutes[self::FULL_URI_SECOND][Route::GET]);
    }

    public function testMiddleware(): void
    {
        $this->route->addMiddleware([
            new Middleware(self::FILTER_NAME_1, $this->customClass::class, 'exampleMethod1'),
            new Middleware(self::FILTER_NAME_2, $this->customClass::class, 'exampleMethod2')
        ]);

        $this->route->middleware(
            [self::FILTER_NAME_1, self::FILTER_NAME_2, 'prefix' => self::PREFIX],
            function (): void {
                $this->route->get(self::URI, fn () => self::ARRAY_RESPONSE);
            }
        );

        $fullRoutes = $this->route->getFullRoutes();

        $this->assertIsArray($fullRoutes);
        $this->assertArrayHasKey(self::FULL_URI, $fullRoutes);
        $this->assertArrayHasKey(Route::GET, $fullRoutes[self::FULL_URI]);
        $this->assertSame(self::DATA_METHOD_MIDDLEWARE, $fullRoutes[self::FULL_URI][Route::GET]);
    }

    public function testMiddlewareAPI(): void
    {
        $this->assertJsonContent($this->client->post(self::API_TEST)->getBody()->getContents(), self::JSON_RESPONSE);
    }

    public function testMultipleMiddleware(): void
    {
        $this->route->addMiddleware([
            new Middleware(self::FILTER_NAME_1, $this->customClass::class, 'exampleMethod1'),
            new Middleware(self::FILTER_NAME_2, $this->customClass::class, 'exampleMethod2'),
            new Middleware(self::FILTER_NAME_3, $this->customClass::class, 'exampleMethod3'),
        ]);

        $this->route->middleware([self::FILTER_NAME_1], function () {
            $this->route->middleware([self::FILTER_NAME_2], function () {
                $this->route->prefix(self::PREFIX, function () {
                    $this->route->get(self::URI, fn () => self::ARRAY_RESPONSE);
                });
            });
        });

        $fullRoutes = $this->route->getFullRoutes();

        $this->assertArrayHasKey(self::FULL_URI, $fullRoutes);
        $this->assertArrayHasKey(Route::GET, $fullRoutes[self::FULL_URI]);
        $this->assertSame(self::DATA_METHOD_MIDDLEWARE, $fullRoutes[self::FULL_URI][Route::GET]);
    }
}
