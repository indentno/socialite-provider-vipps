# Socialite Provider for Vipps

> Custom provider for using Vipps with Laravel Socialite. This package requires laravel socialite in your project.

## 1. Installation

```bash
composer require indentno/socialite-provider-vipps
```

## 2. Event Listener

### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('vipps', \Indent\SocialiteProviderVipps\Provider::class);
});
```
<details>
<summary>
Laravel 10 or below
</summary>
Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \Indent\SocialiteProviderVipps\VippsExtendSocialite::class . '@handle',
    ],
];
```
</details>

## 3. Add configuration to `config/services.php`

```php
'vipps' => [
    'client_id' => env('VIPPS_CLIENT_ID'),
    'client_secret' => env('VIPPS_CLIENT_SECRET'),
    'redirect' => env('VIPPS_REDIRECT_URI'),
    
    // Optional. Can be provided for interacting with vipps test api.
    'base_url' => 'apitest.vipps.no',
    
    // Optional. Can be added in order to request more data.
    // (See link for list of scopes: https://api.vipps.no/access-management-1.0/access/.well-known/openid-configuration)
    'scopes' => [
        'name',
        'email',
    ],
],
```

Remember to whitelist the redirect_uri in the Vipps portal.

## 4. Usage

To initiate the Vipps login, add this to your controller

```php
return Socialite::driver('vipps')->redirect();
```

You've now gotten a user token from Vipps in your callback function. Now we need to 
use the user token to get the phone number of the authenticated user.

```php
$user = Socialite::driver('vipps')->user();
```

Example for a VippsAuthController:

```php
<?php
 
 namespace App\Http\Controllers;
 
 use App\Http\Controllers\Controller;
 use Illuminate\Http\RedirectResponse;
 use Laravel\Socialite\Facades\Socialite;
 
 class VippsAuthController extends Controller
 {
     public function redirect(): RedirectResponse
     {
         return Socialite::driver('vipps')->redirect();
     }
 
     public function callback()
     {
         $user = Socialite::driver('vipps')->user();
 
         if (!$user) {
             // Return error message
         }

         // Verify user exists, authenticate and redirect
     }
}
```

## Vipps guidelines

When using Vipps login you need to use the login button svgs provided by Vipps.
Go to [Vipps brand guidelines](https://developer.vippsmobilepay.com/docs/knowledge-base/design-guidelines/) for more info.

## License

MIT © [Indent AS](https://www.indent.no)
