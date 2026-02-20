<?php

declare(strict_types=1);

namespace Staxxer\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Staxxer\OAuth2\Client\Provider\Exception\MoneybirdIdentityProviderException;

class Moneybird extends AbstractProvider
{
    use BearerAuthorizationTrait;

    const BASE_URL = 'https://moneybird.com';

    public function getBaseAuthorizationUrl()
    {
        return self::BASE_URL . '/oauth/authorize';
    }

    public function getBaseAccessTokenUrl(array $params)
    {
        return self::BASE_URL . '/oauth/token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return self::BASE_URL . '/api/v2/administrations.json';
    }

    protected function getDefaultScopes()
    {
        return ['sales_invoices'];
    }

    protected function getScopeSeparator()
    {
        return ' ';
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        if ($response->getStatusCode() >= 400) {
            throw MoneybirdIdentityProviderException::clientException($response, $data);
        }

        if (isset($data['error']) && is_string($data['error'])) {
            throw MoneybirdIdentityProviderException::oauthException($response, $data);
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        if (count($response) !== 1) {
            throw new RuntimeException(sprintf(
                'Expected exactly one Moneybird administration, got %d',
                count($response)
            ));
        }

        return new MoneybirdResourceOwner($response[0]);
    }

    /**
     * @return array<string, string>
     */
    protected function getDefaultHeaders()
    {
        return [
            'Accept' => 'application/json',
        ];
    }
}
