<?php

declare(strict_types=1);

namespace Tests\Provider;

trait HttpProviderTrait
{
    public static function checkUrlProvider(): array
    {
        return [
            [
                'requestUri' => 'api/users/1',
                'uri' => 'api/users/{idusers}',
                'response' => true,
            ],
            [
                'requestUri' => 'api/users',
                'uri' => 'api/users',
                'response' => true,
            ],
            [
                'requestUri' => 'api/users/1',
                'uri' => 'api/users/{idusers}/example',
                'response' => false,
            ],
            [
                'requestUri' => 'api/users',
                'uri' => 'api/users/{idusers}/example',
                'response' => false,
            ]
        ];
    }
}
