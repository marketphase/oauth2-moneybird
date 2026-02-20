<?php

declare(strict_types=1);

namespace Staxxer\OAuth2\Client\Provider\Exception;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Http\Message\ResponseInterface;

class MoneybirdIdentityProviderException extends IdentityProviderException
{
    /**
     * @param mixed $data
     * @return self
     */
    public static function clientException(ResponseInterface $response, $data)
    {
        $message = $response->getReasonPhrase();

        if (is_array($data) && isset($data['error'])) {
            $message = isset($data['error_description']) ? $data['error_description'] : $data['error'];
        }

        return new self(
            (string) $message,
            $response->getStatusCode(),
            (string) $response->getBody()
        );
    }

    /**
     * @param mixed $data
     * @return self
     */
    public static function oauthException(ResponseInterface $response, $data)
    {
        $message = '';

        if (is_array($data)) {
            $message = isset($data['error_description']) ? $data['error_description'] : (isset($data['error']) ? $data['error'] : '');
        }

        return new self(
            (string) $message,
            $response->getStatusCode(),
            (string) $response->getBody()
        );
    }
}
