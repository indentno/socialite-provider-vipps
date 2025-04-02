<?php

namespace Indent\SocialiteProviderVipps;

use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    public const IDENTIFIER = 'VIPPS';

    protected $scopes = [
        'openid',
        'phoneNumber',
    ];

    protected $scopeSeparator = ' ';

    private ?array $localUrlCache = null;

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->resolveEndpointUrl('authorization_endpoint'), $state);
    }

    protected function getTokenUrl()
    {
        return $this->resolveEndpointUrl('token_endpoint');
    }

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
    protected function mapUserToObject(array $user): \Laravel\Socialite\Two\User
    {
        return (new User())->setRaw($user)->map([
            'phone_number' => $user['phone_number'],
            'name' => $user['name'] ?? null,
            'email' => $user['email'] ?? null,
            'id' => $user['sub'] ?? null,
        ]);
    }

    protected function getTokenHeaders($code): array
    {
        return [
            'Accept' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
        ];
    }

    private function resolveEndpointUrl($endpoint)
    {
        if (! $this->localUrlCache) {
            $this->buildLocalUrlCache();
        }

        return $this->localUrlCache[$endpoint];
    }

    private function buildLocalUrlCache(): void
    {
        $baseUrl = $this->getConfig('base_url', 'api.vipps.no');
        $response = $this->getHttpClient()->get(
            "https://$baseUrl/access-management-1.0/access/.well-known/openid-configuration",
        );

        $this->localUrlCache = json_decode($response->getBody(), true);
    }

    public static function additionalConfigKeys(): array
    {
        return [
            'base_url',
            'scopes',
        ];
    }
}
