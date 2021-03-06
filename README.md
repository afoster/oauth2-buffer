# Buffer Provider for OAuth 2.0 Client

This package provides Buffer OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

[![Build Status](https://travis-ci.org/tgallice/oauth2-buffer.png?branch=master)](https://travis-ci.org/tgallice/oauth2-buffer)
[![Code Coverage](https://scrutinizer-ci.com/g/tgallice/oauth2-buffer/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/tgallice/oauth2-buffer/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/tgallice/oauth2-buffer/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/tgallice/oauth2-buffer/?branch=master)

## Installation

To install, use composer:

```
composer require tgallice/oauth2-buffer
```

## Usage

Usage is the same as The League's OAuth client, using `\Tgallice\OAuth2\Client\Provider\Buffer` as the provider.

### Authorization Code Flow

```php
$provider = new Tgallice\OAuth2\Client\Provider\Buffer([
    'clientId'          => '{buffer-client-id}',
    'clientSecret'      => '{buffer-client-secret}',
    'redirectUri'       => 'https://example.com/callback-url',
]);

if (!isset($_GET['code'])) {

    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: '.$authUrl);
    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

    unset($_SESSION['oauth2state']);
    exit('Invalid state');

} else {

    // Try to get an access token (using the authorization code grant)
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);

    // Optional: Now you have a token you can look up a users profile data
    try {

        // We got an access token, let's now get the user's details
        $user = $provider->getResourceOwner($token);

        // Use these details to create a new profile
        printf('Hello %s!', $user->getName());

    } catch (Exception $e) {

        // Failed to get user details
        exit('Oh dear...');
    }

    // Use this to interact with an API on the users behalf
    echo $token->getToken();
}
```

## Testing

``` bash
$ ./vendor/bin/phpunit
```

## License

The MIT License (MIT). Please see [License File](https://github.com/tgallice/oauth2-buffer/blob/master/LICENSE) for more information.
