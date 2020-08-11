<?php

namespace SocialiteProviders\Vipps;

use SocialiteProviders\Manager\SocialiteWasCalled;

class VippsExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('vipps', Provider::class);
    }
}
