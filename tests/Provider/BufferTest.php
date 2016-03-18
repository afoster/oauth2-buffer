<?php

namespace Tgallice\OAuth2\Client\Test\Provider;

use GuzzleHttp\ClientInterface;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use Prophecy\Argument;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tgallice\OAuth2\Client\Provider\Buffer;

class FooBufferProvider extends Buffer
{
    private $ownerDetails;

    public function __construct(array $options = [], array $ownerDetails = []) {

        parent::__construct($options);

        $this->ownerDetails = $ownerDetails;
    }

    protected function fetchResourceOwnerDetails(AccessToken $token)
    {
        return $this->ownerDetails;
    }
}

class BufferTest extends \PHPUnit_Framework_TestCase
{
    protected $config = [
        'clientId'     => 'mock_client_id',
        'clientSecret' => 'mock_secret',
        'redirectUri'  => 'none',
    ];

    /**
     * @var Buffer
     */
    private $provider;

    public function setUp()
    {
        $this->provider = new Buffer($this->config);
    }

    public function testGetApiUrl()
    {
        $this->assertEquals('https://api.bufferapp.com/1', $this->provider->getApiUrl());
    }

    public function testGetBaseAccessTokenUrl()
    {
        $this->assertEquals($this->provider->getApiUrl() . '/oauth2/token.json', $this->provider->getBaseAccessTokenUrl([]));
    }

    public function testGetUrlUserDetails()
    {
        $token = $this->getAccessToken();
        $this->assertEquals($this->provider->getApiUrl() . '/user.json', $this->provider->getResourceOwnerDetailsUrl($token));
    }

    public function testAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('approval_prompt', $query);
        $this->assertNotNull($this->provider->getState());
    }

    public function testGetResourceOwner()
    {
        $ownerData = ['id' => 'buffer_user_id' , 'name' => 'user name'];
        $provider = new FooBufferProvider($this->config, $ownerData);

        $token = $this->getAccessToken();
        $bufferUser = $provider->getResourceOwner($token);

        $this->assertInstanceOf(ResourceOwnerInterface::class, $bufferUser);
        $this->assertEquals('buffer_user_id', $bufferUser->getId());
        $this->assertEquals('user name', $bufferUser->getName());
        $this->assertEquals($ownerData, $bufferUser->toArray());
    }

    public function testGetAccessToken()
    {
        $response = $this->prophesize(ResponseInterface::class);
        $response->getBody()->willReturn('{"access_token":"mock_access_token"}');
        $response->getHeader('content-type')->willReturn('json');
        $response->getStatusCode()->willReturn(200);

        $client = $this->prophesize(ClientInterface::class);
        $client->send(Argument::type(RequestInterface::class))->willReturn($response);

        $this->provider->setHttpClient($client->reveal());

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertNull($token->getExpires());
        $this->assertNull($token->getRefreshToken());
        $this->assertNull($token->getResourceOwnerId());
    }

    /**
     * @expectedException \Tgallice\OAuth2\Client\Provider\Exception\BufferProviderException
     */
    public function testExceptionThrownWhenErrorObjectReceived()
    {
        $response = $this->prophesize(ResponseInterface::class);
        $response->getBody()->willReturn('{"error": "error_message"}');
        $response->getHeader('content-type')->willReturn('json');
        $response->getStatusCode()->willReturn(400);

        $client = $this->prophesize(ClientInterface::class);
        $client->send(Argument::type(RequestInterface::class))->willReturn($response);

        $this->provider->setHttpClient($client->reveal());

        $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }

    /**
     * @return AccessToken
     */
    private function getAccessToken()
    {
        return $this->prophesize(AccessToken::class)->reveal();
    }
}
