<?php

namespace SocialiteProviders\Vipps;

use GuzzleHttp\ClientInterface;
use SocialiteProviders\Manager\OAuth2\User;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

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
        'phoneNumber',
        'api_version_2',
    ];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    private $localUrlCache = null;

    public static function additionalConfigKeys()
    {
        return ['base_url', 'additional_scopes'];
    }

    public function getScopes()
    {
        return array_merge(
            parent::getScopes(),
            $this->getConfig('additional_scopes', [])
        );
    }

    protected function baseUrl()
    {
        return $this->getConfig('base_url', 'api.vipps.no');
    }

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
        $postKey = (version_compare($this->getGuzzleVersion(), '6') === 1) ? 'form_params' : 'body';

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
        $baseUrl = $this->baseUrl();

        $response = $this->getHttpClient()->get(
            "https://{$baseUrl}/access-management-1.0/access/.well-known/openid-configuration"
        );

        $this->localUrlCache = json_decode($response->getBody(), true);
    }

    private function getGuzzleVersion()
    {
        if (defined(ClientInterface::class . '::VERSION')) {
            return ClientInterface::VERSION;
        }

        return ClientInterface::MAJOR_VERSION;
    }
}
