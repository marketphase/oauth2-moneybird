<?php

declare(strict_types=1);

namespace Staxxer\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Override;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Staxxer\OAuth2\Client\Provider\Exception\MoneybirdIdentityProviderException;

class Moneybird extends AbstractProvider
{
    use BearerAuthorizationTrait;

    public const string BASE_URL = 'https://moneybird.com';

    #[Override]
    public function getBaseAuthorizationUrl(): string
    {
        return self::BASE_URL . '/oauth/authorize';
    }

    #[Override]
    public function getBaseAccessTokenUrl(array $params): string
    {
        return self::BASE_URL . '/oauth/token';
    }

    #[Override]
    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return self::BASE_URL . '/api/v2/administrations.json';
    }

    #[Override]
    protected function getDefaultScopes(): array
    {
        return ['sales_invoices'];
    }

    #[Override]
    protected function getScopeSeparator(): string
    {
        return ' ';
    }

    #[Override]
    protected function checkResponse(ResponseInterface $response, $data): void
    {
        if ($response->getStatusCode() >= 400) {
            throw MoneybirdIdentityProviderException::clientException($response, $data);
        }

        if (isset($data['error']) && is_string($data['error'])) {
            throw MoneybirdIdentityProviderException::oauthException($response, $data);
        }
    }

    #[Override]
    protected function createResourceOwner(
        array $response,
        AccessToken $token,
    ): ResourceOwnerInterface {
        if (count($response) !== 1) {
            throw new RuntimeException(sprintf(
                'Expected exactly one Moneybird administration, got %d',
                count($response),
            ));
        }

        return new MoneybirdResourceOwner($response[0]);
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function getDefaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
        ];
    }
}
