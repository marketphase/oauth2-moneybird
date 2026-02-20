# Moneybird Provider for OAuth 2.0 Client

This package provides Moneybird OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Installation

```bash
composer require staxxer/oauth2-moneybird
```

## Usage

```php
use Staxxer\OAuth2\Client\Provider\Moneybird;

$provider = new Moneybird([
    'clientId'     => '{moneybird-client-id}',
    'clientSecret' => '{moneybird-client-secret}',
    'redirectUri'  => 'https://example.com/callback',
]);
```

### Authorization

```php
// Get authorization URL
$authorizationUrl = $provider->getAuthorizationUrl();

// Store state for CSRF validation
$_SESSION['oauth2state'] = $provider->getState();

// Redirect user
header('Location: ' . $authorizationUrl);
```

### Access Token

```php
// After redirect back from Moneybird
$token = $provider->getAccessToken('authorization_code', [
    'code' => $_GET['code'],
]);

echo $token->getToken();
echo $token->getRefreshToken();
```

### Refresh Token

```php
$token = $provider->getAccessToken('refresh_token', [
    'refresh_token' => $existingToken->getRefreshToken(),
]);
```

### Resource Owner (Administration)

```php
$resourceOwner = $provider->getResourceOwner($token);

// Administration ID
echo $resourceOwner->getId();

// Administration name
echo $resourceOwner->getName();
```

## Testing

```bash
composer install
vendor/bin/phpunit
```

## License

MIT
