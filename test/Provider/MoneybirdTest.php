<?php

declare(strict_types=1);

namespace Staxxer\OAuth2\Client\Test\Provider;

use GuzzleHttp\ClientInterface;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Staxxer\OAuth2\Client\Provider\Moneybird;
use Staxxer\OAuth2\Client\Provider\MoneybirdResourceOwner;

class MoneybirdTest extends TestCase
{
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new Moneybird([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'https://example.com/callback',
        ]);
    }

    public function testAuthorizationUrl(): void
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        self::assertSame('/oauth/authorize', $uri['path']);
        self::assertArrayHasKey('client_id', $query);
        self::assertArrayHasKey('redirect_uri', $query);
        self::assertArrayHasKey('state', $query);
        self::assertArrayHasKey('scope', $query);
        self::assertArrayHasKey('response_type', $query);
        self::assertSame('code', $query['response_type']);
        self::assertNotNull($this->provider->getState());
    }

    public function testBaseAccessTokenUrl(): void
    {
        $url = $this->provider->getBaseAccessTokenUrl([]);

        self::assertSame('https://moneybird.com/oauth/token', $url);
    }

    public function testResourceOwnerDetailsUrl(): void
    {
        $token = new AccessToken(['access_token' => 'mock_token']);
        $url = $this->provider->getResourceOwnerDetailsUrl($token);

        self::assertSame('https://moneybird.com/api/v2/administrations.json', $url);
    }

    public function testDefaultScopes(): void
    {
        $url = $this->provider->getAuthorizationUrl();
        parse_str(parse_url($url, PHP_URL_QUERY), $query);

        self::assertSame('sales_invoices', $query['scope']);
    }

    public function testGetAccessToken(): void
    {
        $response = $this->createMockResponse(200, json_encode([
            'access_token' => 'mock_access_token',
            'token_type' => 'bearer',
            'refresh_token' => 'mock_refresh_token',
        ]));

        $client = $this->createMockHttpClient($response);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', [
            'code' => 'mock_authorization_code',
        ]);

        self::assertSame('mock_access_token', $token->getToken());
        self::assertSame('mock_refresh_token', $token->getRefreshToken());
        self::assertNull($token->getExpires());
    }

    public function testGetResourceOwner(): void
    {
        $tokenResponse = $this->createMockResponse(200, json_encode([
            'access_token' => 'mock_access_token',
            'token_type' => 'bearer',
        ]));

        $administrations = [
            ['id' => 123456789, 'name' => 'Test Administration'],
        ];

        $resourceOwnerResponse = $this->createMockResponse(200, json_encode($administrations));

        $responses = [$tokenResponse, $resourceOwnerResponse];
        $client = $this->createMock(ClientInterface::class);
        $client->method('send')
            ->willReturnCallback(function () use (&$responses) {
                return array_shift($responses);
            });

        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', [
            'code' => 'mock_authorization_code',
        ]);

        $resourceOwner = $this->provider->getResourceOwner($token);

        self::assertInstanceOf(MoneybirdResourceOwner::class, $resourceOwner);
        self::assertSame('123456789', $resourceOwner->getId());
        self::assertSame('Test Administration', $resourceOwner->getName());
    }

    public function testGetResourceOwnerThrowsWhenMultipleAdministrations(): void
    {
        $tokenResponse = $this->createMockResponse(200, json_encode([
            'access_token' => 'mock_access_token',
            'token_type' => 'bearer',
        ]));

        $administrations = [
            ['id' => 123456789, 'name' => 'First'],
            ['id' => 987654321, 'name' => 'Second'],
        ];

        $resourceOwnerResponse = $this->createMockResponse(200, json_encode($administrations));

        $responses = [$tokenResponse, $resourceOwnerResponse];
        $client = $this->createMock(ClientInterface::class);
        $client->method('send')
            ->willReturnCallback(function () use (&$responses) {
                return array_shift($responses);
            });

        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', [
            'code' => 'mock_authorization_code',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Expected exactly one Moneybird administration, got 2');

        $this->provider->getResourceOwner($token);
    }

    public function testGetResourceOwnerThrowsWhenNoAdministrations(): void
    {
        $tokenResponse = $this->createMockResponse(200, json_encode([
            'access_token' => 'mock_access_token',
            'token_type' => 'bearer',
        ]));

        $resourceOwnerResponse = $this->createMockResponse(200, json_encode([]));

        $responses = [$tokenResponse, $resourceOwnerResponse];
        $client = $this->createMock(ClientInterface::class);
        $client->method('send')
            ->willReturnCallback(function () use (&$responses) {
                return array_shift($responses);
            });

        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', [
            'code' => 'mock_authorization_code',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Expected exactly one Moneybird administration, got 0');

        $this->provider->getResourceOwner($token);
    }

    public function testClientErrorThrowsException(): void
    {
        $response = $this->createMockResponse(401, json_encode([
            'error' => 'unauthorized',
            'error_description' => 'The access token is invalid',
        ]));

        $client = $this->createMockHttpClient($response);
        $this->provider->setHttpClient($client);

        $this->expectException(IdentityProviderException::class);
        $this->expectExceptionMessage('The access token is invalid');

        $this->provider->getAccessToken('authorization_code', [
            'code' => 'mock_authorization_code',
        ]);
    }

    public function testClientErrorWithoutDescriptionUsesError(): void
    {
        $response = $this->createMockResponse(400, json_encode([
            'error' => 'invalid_request',
        ]));

        $client = $this->createMockHttpClient($response);
        $this->provider->setHttpClient($client);

        $this->expectException(IdentityProviderException::class);
        $this->expectExceptionMessage('invalid_request');

        $this->provider->getAccessToken('authorization_code', [
            'code' => 'mock_authorization_code',
        ]);
    }

    public function testOAuthErrorThrowsException(): void
    {
        $response = $this->createMockResponse(200, json_encode([
            'error' => 'invalid_grant',
            'error_description' => 'The authorization code has expired',
        ]));

        $client = $this->createMockHttpClient($response);
        $this->provider->setHttpClient($client);

        $this->expectException(IdentityProviderException::class);
        $this->expectExceptionMessage('The authorization code has expired');

        $this->provider->getAccessToken('authorization_code', [
            'code' => 'mock_authorization_code',
        ]);
    }

    public function testDefaultHeaders(): void
    {
        $tokenResponse = $this->createMockResponse(200, json_encode([
            'access_token' => 'mock_access_token',
            'token_type' => 'bearer',
        ]));

        $resourceOwnerResponse = $this->createMockResponse(200, json_encode([
            ['id' => 123456789, 'name' => 'Test'],
        ]));

        $client = $this->createMock(ClientInterface::class);
        $client->method('send')
            ->willReturnCallback(function ($request) use (&$tokenResponse, &$resourceOwnerResponse) {
                $uri = (string) $request->getUri();
                if (strpos($uri, '/oauth/token') !== false) {
                    return $tokenResponse;
                }

                self::assertSame('application/json', $request->getHeaderLine('Accept'));

                return $resourceOwnerResponse;
            });

        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', [
            'code' => 'mock_authorization_code',
        ]);

        $this->provider->getResourceOwner($token);
    }

    /**
     * @return ResponseInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createMockResponse($statusCode, $body)
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn($body);
        $stream->method('getContents')->willReturn($body);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('getBody')->willReturn($stream);
        $response->method('getReasonPhrase')->willReturn('');
        $response->method('getHeader')->willReturn(['application/json']);

        return $response;
    }

    /**
     * @return ClientInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createMockHttpClient(ResponseInterface $response)
    {
        $client = $this->createMock(ClientInterface::class);
        $client->method('send')->willReturn($response);

        return $client;
    }
}
