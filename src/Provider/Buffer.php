<?php

namespace Tgallice\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;
use Tgallice\OAuth2\Client\Provider\Exception\BufferProviderException;

class Buffer extends AbstractProvider
{
    /**
     * Buffer app base url
     *
     * @const string
     */
    const BASE_BUFFER_URL = 'https://bufferapp.com';

    /**
     * Buffer API base url
     *
     * @const string
     */
    const BASE_BUFFER_API_URL = 'https://api.bufferapp.com';

    /**
     * Buffer API version
     *
     * @const string
     */
    const BUFFER_API_VERSION = 1;

    /**
     * @inheritdoc
     */
    public function getBaseAuthorizationUrl()
    {
        return static::BASE_BUFFER_URL . '/oauth2/authorize';
    }

    /**
     * @inheritdoc
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->getApiUrl() . '/oauth2/token.json';
    }

    /**
     * @inheritdoc
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->getApiUrl() . '/user.json?access_token=' . $token;
    }

    /**
     * Get the Buffer API URL
     *
     * @return string
     */
    public function getApiUrl()
    {
        return static::BASE_BUFFER_API_URL . '/' . static::BUFFER_API_VERSION;
    }

    /**
     * @inheritdoc
     */
    protected function getDefaultScopes()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    protected function getAuthorizationHeaders($token = null)
    {
        return [
            'Authorization' => 'Bearer '. (string) $token,
        ];
    }

    /**
     * @inheritdoc
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if ($response->getStatusCode() >= 400) {
            throw new BufferProviderException(
                $data['error'],
                isset($data['code']) ? (int) $data['code'] : $response->getStatusCode(),
                $response
            );
        }
    }

    /**
     * @inheritdoc
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new BufferUser($response);
    }
}
