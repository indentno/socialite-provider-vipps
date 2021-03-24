<?php

namespace SocialiteProviders\Vipps;

use GuzzleHttp\ClientInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'VIPPS';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        'openid',
        'api_version_2',
        'phoneNumber',
    ];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    private $localUrlCache = null;

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->resolveEndpointUrl('authorization_endpoint'), $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->resolveEndpointUrl('token_endpoint');
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->resolveEndpointUrl('userinfo_endpoint'), [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'phone_number' => $user['phone_number'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUrl,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
        $postKey = (version_compare(ClientInterface::VERSION, '6') === 1) ? 'form_params' : 'body';

        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
            ],
            $postKey => $this->getTokenFields($code),
        ]);

        return json_decode($response->getBody(), true);
    }

    private function resolveEndpointUrl($endpoint)
    {
        if (! $this->localUrlCache) {
            $this->buildLocalUrlCache();
        }

        return $this->localUrlCache[$endpoint];
    }

    private function buildLocalUrlCache()
    {
        $response = $this->getHttpClient()->get(
            config('services.vipps.client_base_uri', 'https://api.vipps.no')
            . '/access-management-1.0/access/.well-known/openid-configuration'
        );

        $this->localUrlCache = json_decode($response->getBody(), true);
    }
}
